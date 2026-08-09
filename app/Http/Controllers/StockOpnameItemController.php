<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordCountRequest;
use App\Models\StockOpnameItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StockOpnameItemController extends Controller
{
    /**
     * Record a physical count for a single line. This is the action a
     * Supervisor hits after scanning/counting a product during an In
     * Progress opname — see StockOpnamePolicy::recordCount() for the
     * authorization rule (must be the specifically assigned Supervisor,
     * or a Warehouse Admin/Super Admin with access to the warehouse).
     */
    public function recordCount(RecordCountRequest $request, StockOpnameItem $stockOpnameItem): RedirectResponse
    {
        $this->authorize('recordCount', $stockOpnameItem->stockOpname);

        $stockOpnameItem->recordCount(
            $request->validated('physical_qty'),
            Auth::user(),
        );

        return back()->with('success', "Counted: {$stockOpnameItem->product_name_snapshot}.");
    }
}
