<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Unit::class);

        return Inertia::render('Units/Index', [
            'units' => Unit::query()->withCount('products')->orderBy('name')->paginate(15),
        ]);
    }

    public function store(StoreUnitRequest $request): RedirectResponse
    {
        $this->authorize('create', Unit::class);

        Unit::query()->create($request->validated());

        return back()->with('success', 'Unit created.');
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $this->authorize('update', $unit);

        $unit->update($request->validated());

        return back()->with('success', 'Unit updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $this->authorize('delete', $unit);

        $unit->delete();

        return back()->with('success', 'Unit deleted.');
    }
}
