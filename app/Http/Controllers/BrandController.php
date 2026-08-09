<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Brand::class);

        return Inertia::render('brands/index', [
            'brands' => Brand::query()->withCount('products')->orderBy('name')->paginate(15),
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $this->authorize('create', Brand::class);

        Brand::query()->create($request->validated());

        return back()->with('success', 'Brand created.');
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->authorize('update', $brand);

        $brand->update($request->validated());

        return back()->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        $brand->delete();

        return back()->with('success', 'Brand deleted.');
    }
}
