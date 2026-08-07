<?php

namespace App\Http\Controllers;

use App\Enums\WarehouseTransferStatus;
use App\Http\Requests\StoreWarehouseTransferRequest;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseTransferController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', WarehouseTransfer::class);

        $user = Auth::user();
        $query = WarehouseTransfer::query()->with(['fromWarehouse', 'toWarehouse', 'transferredBy', 'receivedBy']);

        $warehouses = $user->hasRole('Super Admin')
            ? Warehouse::query()->orderBy('name')->get(['id', 'code', 'name'])
            : $user->warehouses()->orderBy('name')->get(['warehouses.id', 'warehouses.code', 'warehouses.name']);

        if (! $user->hasRole('Super Admin')) {
            $warehouseIds = $warehouses->pluck('id');
            $query->where(fn ($q) => $q
                ->whereIn('from_warehouse_id', $warehouseIds)
                ->orWhereIn('to_warehouse_id', $warehouseIds));
        }

        return Inertia::render('warehouse-transfers/index', [
            'warehouseTransfers' => $query->latest('date')->paginate(15),
            // All warehouses, not just $warehouses — a transfer's "to" side
            // can be a warehouse the creator doesn't have access to yet
            // (they're initiating a transfer TO it, not managing it).
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'code', 'name']),
            'products' => Product::query()
                ->whereIn('warehouse_id', $warehouses->pluck('id'))
                ->get(['id', 'sku', 'name', 'warehouse_id', 'stock']),
        ]);
    }

    public function show(WarehouseTransfer $warehouseTransfer): Response
    {
        $this->authorize('view', $warehouseTransfer);

        return Inertia::render('warehouse-transfers/show', [
            'warehouseTransfer' => $warehouseTransfer->load([
                'fromWarehouse', 'toWarehouse', 'transferredBy', 'receivedBy', 'items.product',
            ]),
        ]);
    }

    public function store(StoreWarehouseTransferRequest $request): RedirectResponse
    {
        $this->authorize('create', WarehouseTransfer::class);

        $validated = $request->validated();

        $warehouseTransfer = DB::transaction(function () use ($validated) {
            $warehouseTransfer = WarehouseTransfer::query()->create([
                'from_warehouse_id' => $validated['from_warehouse_id'],
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'transferred_by' => Auth::id(),
                'transfer_number' => $this->generateTransferNumber(),
                'date' => $validated['date'],
                'status' => WarehouseTransferStatus::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);

                $warehouseTransfer->items()->create([
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                ]);
            }

            return $warehouseTransfer;
        });

        return redirect()
            ->route('warehouse-transfers.show', $warehouseTransfer)
            ->with('success', 'Warehouse transfer created as Pending.');
    }

    public function markInTransit(WarehouseTransfer $warehouseTransfer): RedirectResponse
    {
        $this->authorize('markInTransit', $warehouseTransfer);

        try {
            $warehouseTransfer->markInTransit();
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Transfer marked In Transit — stock deducted from source.');
    }

    public function complete(WarehouseTransfer $warehouseTransfer): RedirectResponse
    {
        $this->authorize('complete', $warehouseTransfer);

        $warehouseTransfer->complete(Auth::id());

        return back()->with('success', 'Transfer completed — stock added to destination.');
    }

    public function reject(WarehouseTransfer $warehouseTransfer): RedirectResponse
    {
        $this->authorize('reject', $warehouseTransfer);

        $warehouseTransfer->reject();

        return back()->with('success', 'Transfer rejected.');
    }

    private function generateTransferNumber(): string
    {
        return 'WT-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }
}
