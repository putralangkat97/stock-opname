<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRackRequest;
use App\Http\Requests\UpdateRackRequest;
use App\Models\Rack;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;

class RackController extends Controller
{
    public function store(StoreRackRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('create', [Rack::class, $warehouse]);

        $warehouse->racks()->create($request->validated());

        return back()->with('success', 'Rack created.');
    }

    public function update(UpdateRackRequest $request, Warehouse $warehouse, Rack $rack): RedirectResponse
    {
        $this->authorize('update', $rack);

        $rack->update($request->validated());

        return back()->with('success', 'Rack updated.');
    }

    public function destroy(Warehouse $warehouse, Rack $rack): RedirectResponse
    {
        $this->authorize('delete', $rack);

        $rack->delete();

        return back()->with('success', 'Rack deleted.');
    }
}
