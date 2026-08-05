<?php

namespace App\Http\Controllers;

use App\Enums\GoodsIssueStatus;
use App\Http\Requests\StoreGoodsIssueRequest;
use App\Http\Requests\UpdateGoodsIssueRequest;
use App\Models\GoodsIssue;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class GoodsIssueController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', GoodsIssue::class);

        $user = Auth::user();
        $query = GoodsIssue::query()->with(['customer', 'warehouse', 'issuedBy']);

        if (! $user->hasRole('Super Admin')) {
            $query->whereIn('warehouse_id', $user->warehouses()->pluck('warehouses.id'));
        }

        return Inertia::render('GoodsIssues/Index', [
            'goodsIssues' => $query->latest('date')->paginate(15),
        ]);
    }

    public function show(GoodsIssue $goodsIssue): Response
    {
        $this->authorize('view', $goodsIssue);

        return Inertia::render('GoodsIssues/Show', [
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

        // Model throws RuntimeException on insufficient stock — let it bubble
        // up to Laravel's default exception handler (renders as a 500/flash
        // error via Inertia), rather than silently swallowing a real problem.
        $goodsIssue->approve();

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
