<?php

namespace App\Http\Controllers;

use App\Concerns\NotifiesApprovers;
use App\Enums\GoodsIssueStatus;
use App\Http\Requests\StoreGoodsIssueRequest;
use App\Http\Requests\UpdateGoodsIssueRequest;
use App\Models\Customer;
use App\Models\GoodsIssue;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GoodsIssueController extends Controller
{
    use NotifiesApprovers;

    public function index(): Response
    {
        $this->authorize('viewAny', GoodsIssue::class);

        /** @var User $user */
        $user = Auth::user();
        $query = GoodsIssue::query()->with(['customer', 'warehouse', 'issuedBy']);

        $warehouses = $user->hasRole('Super Admin')
            ? Warehouse::query()->orderBy('name')->get(['id', 'code', 'name'])
            : $user->warehouses()->orderBy('name')->get(['warehouses.id', 'warehouses.code', 'warehouses.name']);

        if (! $user->hasRole('Super Admin')) {
            $query->whereIn('warehouse_id', $warehouses->pluck('id'));
        }

        return Inertia::render('goods-issues/index', [
            'goodsIssues' => $query->latest('date')->paginate(15),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => $warehouses,
            // 'stock' included so the form can warn before submitting a qty
            // the product doesn't have — the real guard is still server-side
            // in GoodsIssue::approve(), this is just a UX nicety.
            'products' => Product::query()
                ->whereIn('warehouse_id', $warehouses->pluck('id'))
                ->get(['id', 'sku', 'name', 'warehouse_id', 'stock', 'selling_price']),
        ]);
    }

    public function show(GoodsIssue $goodsIssue): Response
    {
        $this->authorize('view', $goodsIssue);

        return Inertia::render('goods-issues/show', [
            'goodsIssue' => $goodsIssue->load(['customer', 'warehouse', 'issuedBy', 'items.product']),
        ]);
    }

    public function store(StoreGoodsIssueRequest $request): RedirectResponse
    {
        $this->authorize('create', GoodsIssue::class);

        $validated = $request->validated();

        $goodsIssue = DB::transaction(function () use ($validated) {
            $goodsIssue = GoodsIssue::query()->create([
                'customer_id' => $validated['customer_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'issued_by' => Auth::id(),
                'issue_number' => $this->generateIssueNumber(),
                'so_number' => $validated['so_number'] ?? null,
                'date' => $validated['date'],
                'status' => GoodsIssueStatus::STATUS_DRAFT,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncItems($goodsIssue, $validated['items']);

            return $goodsIssue;
        });

        $this->notifyApprovers('Goods Issue', $goodsIssue->issue_number, "/goods-issues/{$goodsIssue->id}");

        return redirect()
            ->route('goods-issues.show', $goodsIssue)
            ->with('success', 'Goods issue created as Draft.');
    }

    public function update(UpdateGoodsIssueRequest $request, GoodsIssue $goodsIssue): RedirectResponse
    {
        $this->authorize('update', $goodsIssue);

        $validated = $request->validated();

        DB::transaction(function () use ($goodsIssue, $validated) {
            $goodsIssue->update([
                'customer_id' => $validated['customer_id'],
                'so_number' => $validated['so_number'] ?? null,
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $goodsIssue->items()->delete();
            $this->syncItems($goodsIssue, $validated['items']);
        });

        return back()->with('success', 'Goods issue updated.');
    }

    public function approve(GoodsIssue $goodsIssue): RedirectResponse
    {
        $this->authorize('approve', $goodsIssue);

        try {
            $goodsIssue->approve();
        } catch (\RuntimeException $e) {
            // Insufficient stock, or wrong status — a real business-rule
            // rejection, not a bug. Surface it as a flash error rather than
            // a raw 500/Inertia error modal.
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Goods issue approved — stock updated.');
    }

    public function cancel(GoodsIssue $goodsIssue): RedirectResponse
    {
        $this->authorize('cancel', $goodsIssue);

        $goodsIssue->cancel();

        return back()->with('success', 'Goods issue cancelled.');
    }

    private function syncItems(GoodsIssue $goodsIssue, array $items): void
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::query()->findOrFail($item['product_id']);
            $subtotal = $item['qty'] * $item['unit_price'];
            $total += $subtotal;

            $goodsIssue->items()->create([
                'product_id' => $product->id,
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $subtotal,
            ]);
        }

        $goodsIssue->update(['total_amount' => $total]);
    }

    private function generateIssueNumber(): string
    {
        return 'GI-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }
}
