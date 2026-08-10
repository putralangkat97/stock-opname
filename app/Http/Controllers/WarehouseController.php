<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Warehouse::class);

        $user = Auth::user();
        $query = Warehouse::query();

        // List-level scoping: a Policy's view() gates one record at a time,
        // it can't filter a paginated list. Non-Super-Admins only ever see
        // their own accessible warehouses here.
        if (! $user->hasRole('Super Admin')) {
            $query->whereIn('id', $user->warehouses()->pluck('warehouses.id'));
        }

        return Inertia::render('warehouses/index', [
            'warehouses' => $query->orderBy('name')->paginate(15),
        ]);
    }

    public function show(Warehouse $warehouse): Response
    {
        $this->authorize('view', $warehouse);

        return Inertia::render('warehouses/show', [
            'warehouse' => $warehouse->load(['racks.binLocations']),
        ]);
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $this->authorize('create', Warehouse::class);

        Warehouse::query()->create($request->validated());

        return back()->with('success', 'Warehouse created.');
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('update', $warehouse);

        $warehouse->update($request->validated());

        return back()->with('success', 'Warehouse updated.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('delete', $warehouse);

        $warehouse->delete();

        return back()->with('success', 'Warehouse deleted.');
    }
}
