<?php

namespace App\Http\Controllers;

use App\Enums\WarehouseTransferStatus;
use App\Http\Requests\StoreWarehouseTransferRequest;
use App\Models\Product;
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

        if (! $user->hasRole('Super Admin')) {
            $warehouseIds = $user->warehouses()->pluck('warehouses.id');
            $query->where(fn ($q) => $q
                ->whereIn('from_warehouse_id', $warehouseIds)
                ->orWhereIn('to_warehouse_id', $warehouseIds));
        }

        return Inertia::render('WarehouseTransfers/Index', [
            'warehouseTransfers' => $query->latest('date')->paginate(15),
        ]);
    }

    public function show(WarehouseTransfer $warehouseTransfer): Response
    {
        $this->authorize('view', $warehouseTransfer);

        return Inertia::render('WarehouseTransfers/Show', [
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

        $warehouseTransfer->markInTransit();

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
