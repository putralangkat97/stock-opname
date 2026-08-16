<?php

namespace App\Domain\StockOpname\Services;

use App\Enums\StockOpnameItemStatus;
use App\Models\StockOpnameItem;

final class StockOpnameVarianceService
{
    public function calculate(StockOpnameItem $item): void
    {
        if ($item->physical_qty === null) {
            $item->update([
                'status' => StockOpnameItemStatus::STATUS_UNCOUNTED,
            ]);

            return;
        }

        $variance = $item->physical_qty - $item->system_qty;

        $status = match (true) {
            $variance === 0 => StockOpnameItemStatus::STATUS_MATCHED,
            $variance > 0 => StockOpnameItemStatus::STATUS_SURPLUS,
            default => StockOpnameItemStatus::STATUS_SHORTAGE,
        };

        $item->update([
            'status' => $status,
        ]);
    }
}
