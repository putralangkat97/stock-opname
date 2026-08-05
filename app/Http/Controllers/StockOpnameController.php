<?php

namespace App\Http\Controllers;

use App\Enums\StockOpnameStatus;
use App\Http\Requests\StoreStockOpnameRequest;
use App\Models\Product;
use App\Models\StockOpname;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        if (! $user->hasRole('Super Admin')) {
            $warehouseIds = $user->warehouses()->pluck('warehouses.id');
            $query->where(fn ($q) => $q
                ->whereIn('warehouse_id', $warehouseIds)
                ->orWhere('assigned_to', $user->id));
        }

        return Inertia::render('StockOpnames/Index', [
            'stockOpnames' => $query->latest('start_date')->paginate(15),
        ]);
    }

    public function show(StockOpname $stockOpname): Response
    {
        $this->authorize('view', $stockOpname);

        return Inertia::render('StockOpnames/Show', [
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

        // Model throws RuntimeException if any line is still uncounted —
        // let it bubble up as a flash error rather than swallowing it.
        $stockOpname->complete();

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
