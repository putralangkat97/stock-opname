<?php

namespace App\Http\Controllers;

use App\Enums\StockOpnameStatus;
use App\Http\Requests\StoreStockOpnameRequest;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', StockOpname::class);

        $user = Auth::user();
        $query = StockOpname::query()->with(['warehouse', 'assignedTo', 'approvedBy']);

        $warehouses = $user->hasRole('Super Admin')
            ? Warehouse::query()->orderBy('name')->get(['id', 'code', 'name'])
            : $user->warehouses()->orderBy('name')->get(['warehouses.id', 'warehouses.code', 'warehouses.name']);

        if (! $user->hasRole('Super Admin')) {
            $warehouseIds = $warehouses->pluck('id');
            $query->where(fn ($q) => $q
                ->whereIn('warehouse_id', $warehouseIds)
                ->orWhere('assigned_to', $user->id));
        }

        return Inertia::render('stock-opnames/index', [
            'stockOpnames' => $query->latest('start_date')->paginate(15),
            'warehouses' => $warehouses,
            'products' => Product::query()
                ->whereIn('warehouse_id', $warehouses->pluck('id'))
                ->get(['id', 'sku', 'name', 'warehouse_id']),
            // Assignable users: anyone with warehouse access (Warehouse Admin
            // or Supervisor), plus their warehouse IDs so the Create dialog
            // can filter the assignee list to the chosen warehouse client-side.
            'assignableUsers' => User::query()
                ->with('warehouses:id')
                ->whereHas('warehouses')
                ->get(['id', 'name'])
                ->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'warehouse_ids' => $u->warehouses->pluck('id'),
                ]),
        ]);
    }

    public function show(StockOpname $stockOpname): Response
    {
        $this->authorize('view', $stockOpname);

        return Inertia::render('stock-opnames/show', [
            'stockOpname' => $stockOpname->load([
                'warehouse', 'assignedTo', 'approvedBy', 'items.product', 'items.scannedBy',
            ]),
        ]);
    }

    public function store(StoreStockOpnameRequest $request): RedirectResponse
    {
        $this->authorize('create', StockOpname::class);

        $validated = $request->validated();

        $stockOpname = DB::transaction(function () use ($validated) {
            $stockOpname = StockOpname::query()->create([
                'warehouse_id' => $validated['warehouse_id'],
                'assigned_to' => $validated['assigned_to'],
                'opname_number' => $this->generateOpnameNumber(),
                'title' => $validated['title'],
                'start_date' => $validated['start_date'],
                'status' => StockOpnameStatus::STATUS_DRAFT,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);

                // Only product_id passed in — system_qty, product_sku_snapshot,
                // and product_name_snapshot are all derived automatically by
                // StockOpnameItem's saving() hook.
                $stockOpname->items()->create([
                    'product_id' => $product->id,
                ]);
            }

            return $stockOpname;
        });

        return redirect()
            ->route('stock-opnames.show', $stockOpname)
            ->with('success', 'Stock opname created as Draft.');
    }

    public function start(StockOpname $stockOpname): RedirectResponse
    {
        $this->authorize('start', $stockOpname);

        $stockOpname->start();

        return back()->with('success', 'Stock opname started — ready for counting.');
    }

    public function complete(StockOpname $stockOpname): RedirectResponse
    {
        $this->authorize('complete', $stockOpname);

        try {
            $stockOpname->complete();
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Stock opname completed — ready for approval.');
    }

    public function approve(StockOpname $stockOpname): RedirectResponse
    {
        $this->authorize('approve', $stockOpname);

        $stockOpname->approve(Auth::id());

        return back()->with('success', 'Stock opname approved — stock adjustments applied.');
    }

    public function reject(StockOpname $stockOpname): RedirectResponse
    {
        $this->authorize('reject', $stockOpname);

        $stockOpname->reject();

        return back()->with('success', 'Stock opname sent back for recount.');
    }

    private function generateOpnameNumber(): string
    {
        return 'OPN-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }
}
