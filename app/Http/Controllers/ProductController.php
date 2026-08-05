<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $user = Auth::user();
        $query = Product::query()->with(['category', 'brand', 'unit', 'warehouse', 'binLocation']);

        // Same list-level scoping pattern as WarehouseController — the Policy's
        // view() only gates a single record, so non-Super-Admins are scoped
        // to their accessible warehouses here.
        if (! $user->hasRole('Super Admin')) {
            $query->whereIn('warehouse_id', $user->warehouses()->pluck('warehouses.id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
        }

        return Inertia::render('Products/Index', [
            'products' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => $request->only(['warehouse_id', 'category_id', 'search']),
        ]);
    }

    public function show(Product $product): Response
    {
        $this->authorize('view', $product);

        return Inertia::render('Products/Show', [
            'product' => $product->load(['category', 'brand', 'unit', 'warehouse', 'binLocation']),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $warehouse = Warehouse::query()->findOrFail($request->input('warehouse_id'));
        $this->authorize('create', [Product::class, $warehouse]);

        Product::query()->create($request->validated());

        return back()->with('success', 'Product created.');
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        // warehouse_id is intentionally not updatable here — moving a
        // product between warehouses is what Warehouse Transfer is for,
        // not a direct edit (it would bypass the stock-movement audit trail).
        $product->update($request->validated());

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }
}
