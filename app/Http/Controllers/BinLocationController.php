<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBinLocationRequest;
use App\Http\Requests\UpdateBinLocationRequest;
use App\Models\BinLocation;
use App\Models\Rack;
use Illuminate\Http\RedirectResponse;

class BinLocationController extends Controller
{
    public function store(StoreBinLocationRequest $request, Rack $rack): RedirectResponse
    {
        $this->authorize('create', [BinLocation::class, $rack]);

        // warehouse_id is derived automatically from rack_id in the
        // BinLocation model's saving() hook — don't set it here.
        $rack->binLocations()->create($request->validated());

        return back()->with('success', 'Bin location created.');
    }

    public function update(UpdateBinLocationRequest $request, Rack $rack, BinLocation $binLocation): RedirectResponse
    {
        $this->authorize('update', $binLocation);

        $binLocation->update($request->validated());

        return back()->with('success', 'Bin location updated.');
    }

    public function destroy(Rack $rack, BinLocation $binLocation): RedirectResponse
    {
        $this->authorize('delete', $binLocation);

        $binLocation->delete();

        return back()->with('success', 'Bin location deleted.');
    }
}
