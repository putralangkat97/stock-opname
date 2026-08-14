<?php

namespace App\Domain\StockOpname\Services;

use App\Enums\StockOpnameStatus;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class StockOpnameCompletionService
{
    public function complete(StockOpname $stockOpname): void
    {
        if ($stockOpname->status !== StockOpnameStatus::STATUS_IN_PROGRESS) {
            throw new RuntimeException(
                'Only an In Progress opname can be completed.',
            );
        }

        if ($stockOpname->items()->whereNull('physical_qty')->exists()) {
            throw new RuntimeException(
                'All lines must be counted before completing the opname.',
            );
        }

        DB::transaction(function () use ($stockOpname): void {
            $items = $stockOpname->items()
                ->with('product')
                ->get();

            $totalSystem = $items->sum('system_qty');
            $totalPhysical = $items->sum('physical_qty');
            $totalVarianceQty = $totalPhysical - $totalSystem;

            $totalVarianceValue = $items->sum(
                fn (StockOpnameItem $item) => ($item->physical_qty - $item->system_qty)
                    * $item->product->cost_price
            );

            $stockOpname->update([
                'status' => StockOpnameStatus::STATUS_COMPLETED,
                'completed_date' => now()->toDateString(),
                'total_system_qty' => $totalSystem,
                'total_physical_qty' => $totalPhysical,
                'total_variance_qty' => $totalVarianceQty,
                'total_variance_value' => $totalVarianceValue,
            ]);

            $stockOpname->logAudit(
                'completed',
                [
                    'variance_qty' => $totalVarianceQty,
                ],
            );
        });
    }
}
