<?php

namespace App\Http\Controllers;

use App\Enums\StockAdjustmentStatus;
use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
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

        $warehouses = $user->hasRole('Super Admin')
            ? Warehouse::query()->orderBy('name')->get(['id', 'code', 'name'])
            : $user->warehouses()->orderBy('name')->get(['warehouses.id', 'warehouses.code', 'warehouses.name']);

        if (! $user->hasRole('Super Admin')) {
            $query->whereIn('warehouse_id', $warehouses->pluck('id'));
        }

        return Inertia::render('stock-adjustments/index', [
            'stockAdjustments' => $query->latest('date')->paginate(15),
            'warehouses' => $warehouses,
            'products' => Product::query()
                ->whereIn('warehouse_id', $warehouses->pluck('id'))
                ->get(['id', 'sku', 'name', 'warehouse_id', 'stock']),
        ]);
    }

    public function show(StockAdjustment $stockAdjustment): Response
    {
        $this->authorize('view', $stockAdjustment);

        return Inertia::render('stock-adjustments/show', [
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

        try {
            $stockAdjustment->approve();
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

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
