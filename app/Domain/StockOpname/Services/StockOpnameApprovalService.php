<?php

namespace App\Domain\StockOpname\Services;

use App\Enums\StockAdjustmentStatus;
use App\Enums\StockAdjustmentType;
use App\Enums\StockOpnameStatus;
use App\Models\StockAdjustment;
use App\Models\StockOpname;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class StockOpnameApprovalService
{
    public function approve(
        StockOpname $stockOpname,
        int $approverId,
    ): void {
        if ($stockOpname->status !== StockOpnameStatus::STATUS_COMPLETED) {
            throw new RuntimeException(
                'Only a Completed opname can be approved.',
            );
        }

        DB::transaction(function () use ($stockOpname, $approverId): void {
            $varianceItems = $stockOpname->items()
                ->whereColumn('physical_qty', '!=', 'system_qty')
                ->get();

            $this->createAdjustmentIfNeeded(
                $stockOpname,
                $approverId,
                StockAdjustmentType::TYPE_IN,
                $varianceItems->filter(
                    fn ($item) => $item->physical_qty > $item->system_qty,
                ),
            );

            $this->createAdjustmentIfNeeded(
                $stockOpname,
                $approverId,
                StockAdjustmentType::TYPE_OUT,
                $varianceItems->filter(
                    fn ($item) => $item->physical_qty < $item->system_qty,
                ),
            );

            $stockOpname->update([
                'status' => StockOpnameStatus::STATUS_APPROVED,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            $stockOpname->logAudit('approved');
        });
    }

    private function createAdjustmentIfNeeded(
        StockOpname $stockOpname,
        int $approverId,
        StockAdjustmentType $type,
        Collection $lines,
    ): void {
        if ($lines->isEmpty()) {
            return;
        }

        $adjustment = StockAdjustment::query()->create([
            'warehouse_id' => $stockOpname->warehouse_id,
            'adjusted_by' => $approverId,
            'adjustment_number' => $this->generateAdjustmentNumber(
                $stockOpname,
                $type,
            ),
            'type' => $type,
            'reason' => 'Correction',
            'date' => now()->toDateString(),
            'status' => StockAdjustmentStatus::STATUS_PENDING,
            'notes' => "Auto-generated from Stock Opname {$stockOpname->opname_number}",
        ]);

        foreach ($lines as $line) {
            $adjustment->items()->create([
                'product_id' => $line->product_id,
                'product_sku_snapshot' => $line->product_sku_snapshot,
                'product_name_snapshot' => $line->product_name_snapshot,
                'qty' => abs(
                    $line->physical_qty - $line->system_qty,
                ),
            ]);
        }

        $adjustment->refresh();

        // Stock mutation remains centralized in StockAdjustment.
        $adjustment->approve();
    }

    private function generateAdjustmentNumber(
        StockOpname $stockOpname,
        StockAdjustmentType $type,
    ): string {
        return 'ADJ-OPN-'
            .$stockOpname->opname_number
            .'-'
            .$type->value
            .'-'
            .Str::upper(Str::random(4));
    }
}
