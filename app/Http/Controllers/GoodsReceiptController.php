<?php

namespace App\Http\Controllers;

use App\Concerns\NotifiesApprovers;
use App\Enums\GoodsReceiptStatus;
use App\Http\Requests\StoreGoodsReceiptRequest;
use App\Http\Requests\UpdateGoodsReceiptRequest;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GoodsReceiptController extends Controller
{
    use NotifiesApprovers;

    public function index(): Response
    {
        $this->authorize('viewAny', GoodsReceipt::class);

        $user = Auth::user();
        $query = GoodsReceipt::query()->with(['supplier', 'warehouse', 'receivedBy']);

        $warehouses = $user->hasRole('Super Admin')
            ? Warehouse::query()->orderBy('name')->get(['id', 'code', 'name'])
            : $user->warehouses()->orderBy('name')->get(['warehouses.id', 'warehouses.code', 'warehouses.name']);

        if (! $user->hasRole('Super Admin')) {
            $query->whereIn('warehouse_id', $warehouses->pluck('id'));
        }

        return Inertia::render('goods-receipts/index', [
            'goodsReceipts' => $query->latest('date')->paginate(15),
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => $warehouses,
            // sku/name only — the Create dialog needs enough to search/pick a
            // line item, not the full Product payload.
            'products' => Product::query()
                ->whereIn('warehouse_id', $warehouses->pluck('id'))
                ->get(['id', 'sku', 'name', 'warehouse_id', 'cost_price']),
        ]);
    }

    public function show(GoodsReceipt $goodsReceipt): Response
    {
        $this->authorize('view', $goodsReceipt);

        return Inertia::render('goods-receipts/show', [
            'goodsReceipt' => $goodsReceipt->load(['supplier', 'warehouse', 'receivedBy', 'items.product']),
        ]);
    }

    public function store(StoreGoodsReceiptRequest $request): RedirectResponse
    {
        $this->authorize('create', GoodsReceipt::class);

        $validated = $request->validated();

        $goodsReceipt = DB::transaction(function () use ($validated) {
            $goodsReceipt = GoodsReceipt::query()->create([
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'received_by' => Auth::id(),
                'receipt_number' => $this->generateReceiptNumber(),
                'po_number' => $validated['po_number'] ?? null,
                'date' => $validated['date'],
                'status' => GoodsReceiptStatus::STATUS_DRAFT,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncItems($goodsReceipt, $validated['items']);

            return $goodsReceipt;
        });

        $this->notifyApprovers('Goods Receipt', $goodsReceipt->receipt_number, "/goods-receipts/{$goodsReceipt->id}");

        return redirect()
            ->route('goods-receipts.show', $goodsReceipt)
            ->with('success', 'Goods receipt created as Draft.');
    }

    public function update(UpdateGoodsReceiptRequest $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $this->authorize('update', $goodsReceipt);

        $validated = $request->validated();

        DB::transaction(function () use ($goodsReceipt, $validated) {
            $goodsReceipt->update([
                'supplier_id' => $validated['supplier_id'],
                'po_number' => $validated['po_number'] ?? null,
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $goodsReceipt->items()->delete();
            $this->syncItems($goodsReceipt, $validated['items']);
        });

        return back()->with('success', 'Goods receipt updated.');
    }

    public function approve(GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $this->authorize('approve', $goodsReceipt);

        try {
            $goodsReceipt->approve();
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Goods receipt approved — stock updated.');
    }

    public function cancel(GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $this->authorize('cancel', $goodsReceipt);

        $goodsReceipt->cancel();

        return back()->with('success', 'Goods receipt cancelled.');
    }

    /**
     * Rebuilds the total_amount from qty * unit_price on every write — never
     * trust a total sent from the client.
     */
    private function syncItems(GoodsReceipt $goodsReceipt, array $items): void
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::query()->findOrFail($item['product_id']);
            $subtotal = $item['qty'] * $item['unit_price'];
            $total += $subtotal;

            $goodsReceipt->items()->create([
                'product_id' => $product->id,
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
            ]);
        }

        $goodsReceipt->update(['total_amount' => $total]);
    }

    private function generateReceiptNumber(): string
    {
        return 'GR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }
}
