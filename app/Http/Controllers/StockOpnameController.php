<?php

namespace App\Http\Controllers;

use App\Actions\StockOpname\ApproveStockOpname;
use App\Actions\StockOpname\CompleteStockOpname;
use App\Actions\StockOpname\CreateStockOpname;
use App\Actions\StockOpname\RejectStockOpname;
use App\Actions\StockOpname\StartStockOpname;
use App\Concerns\NotifiesApprovers;
use App\Http\Requests\StoreStockOpnameRequest;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    use NotifiesApprovers;

    public function index(): Response
    {
        $this->authorize('viewAny', StockOpname::class);

        /** @var User $user */
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

    /**
     * Store stock opname
     */
    public function store(StoreStockOpnameRequest $request, CreateStockOpname $action): RedirectResponse
    {
        $this->authorize('create', StockOpname::class);

        $stockOpname = $action->execute($request->validated());

        return redirect()
            ->route('stock-opnames.show', $stockOpname)
            ->with('success', 'Stock opname created as Draft.');
    }

    /**
     * Start stock opname
     */
    public function start(StockOpname $stockOpname, StartStockOpname $action): RedirectResponse
    {
        $this->authorize('start', $stockOpname);

        $action->execute($stockOpname);

        return back()->with('success', 'Stock opname started — ready for counting.');
    }

    /**
     * Complete stock opname
     */
    public function complete(StockOpname $stockOpname, CompleteStockOpname $action): RedirectResponse
    {
        $this->authorize('complete', $stockOpname);

        $action->execute($stockOpname);

        $this->notifyApprovers('Stock Opname', $stockOpname->opname_number, "/stock-opnames/{$stockOpname->id}");

        return back()->with('success', 'Stock opname completed — ready for approval.');
    }

    /**
     * Approve stock opname
     */
    public function approve(StockOpname $stockOpname, ApproveStockOpname $action): RedirectResponse
    {
        $this->authorize('approve', $stockOpname);

        $action->execute($stockOpname, Auth::id());

        return back()->with('success', 'Stock opname approved — stock adjustments applied.');
    }

    /**
     * Reject stock opname
     */
    public function reject(StockOpname $stockOpname, RejectStockOpname $action): RedirectResponse
    {
        $this->authorize('reject', $stockOpname);

        $action->execute($stockOpname);

        return back()->with('success', 'Stock opname sent back for recount.');
    }
}
