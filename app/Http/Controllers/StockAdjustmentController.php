<?php

namespace App\Http\Controllers;

use App\Enums\StockAdjustmentStatus;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StockAdjustmentController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', StockAdjustment::class);

        $user = Auth::user();
        $query = StockAdjustment::query()->with(['warehouse', 'adjustedBy']);

        if (! $user->hasRole('Super Admin')) {
            $query->whereIn('warehouse_id', $user->warehouses()->pluck('warehouses.id'));
        }

        return Inertia::render('StockAdjustments/Index', [
            'stockAdjustments' => $query->latest('date')->paginate(15),
        ]);
    }

    public function show(StockAdjustment $stockAdjustment): Response
    {
        $this->authorize('view', $stockAdjustment);

        return Inertia::render('StockAdjustments/Show', [
            'stockAdjustment' => $stockAdjustment->load(['warehouse', 'adjustedBy', 'items.product']),
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request): RedirectResponse
    {
        $this->authorize('create', StockAdjustment::class);

        $validated = $request->validated();

        $stockAdjustment = DB::transaction(function () use ($validated) {
            $stockAdjustment = StockAdjustment::query()->create([
                'warehouse_id' => $validated['warehouse_id'],
                'adjusted_by' => Auth::id(),
                'adjustment_number' => $this->generateAdjustmentNumber(),
                'type' => $validated['type'],
                'reason' => $validated['reason'],
                'date' => $validated['date'],
                'status' => StockAdjustmentStatus::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);

                $stockAdjustment->items()->create([
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                ]);
            }

            return $stockAdjustment;
        });

        return redirect()
            ->route('stock-adjustments.show', $stockAdjustment)
            ->with('success', 'Stock adjustment created as Pending.');
    }

    public function approve(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->authorize('approve', $stockAdjustment);

        $stockAdjustment->approve();

        return back()->with('success', 'Stock adjustment approved — stock updated.');
    }

    public function reject(StockAdjustment $stockAdjustment): RedirectResponse
    {
        $this->authorize('reject', $stockAdjustment);

        $stockAdjustment->reject();

        return back()->with('success', 'Stock adjustment rejected.');
    }

    private function generateAdjustmentNumber(): string
    {
        return 'ADJ-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }
}
