<?php

namespace App\Domain\WarehouseTransfer\Services;

use App\Domain\StockMovement\Services\StockMovementService;
use App\Enums\WarehouseTransferStatus;
use App\Models\Product;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WarehouseTransferCompletionService
{
    public function __construct(
        private readonly StockMovementService $stockMovement,
    ) {}

    public function complete(
        WarehouseTransfer $transfer,
        int $receivedByUserId,
    ): void {
        if ($transfer->status !== WarehouseTransferStatus::STATUS_IN_TRANSIT) {
            throw new RuntimeException(
                'Only an In Transit transfer can be completed.',
            );
        }

        DB::transaction(function () use (
            $transfer,
            $receivedByUserId,
        ): void {
            foreach ($transfer->items as $item) {
                $sourceProduct = $item->product;

                $destinationProduct = Product::query()->firstOrCreate(
                    [
                        'sku' => $sourceProduct->sku,
                        'warehouse_id' => $transfer->to_warehouse_id,
                    ],
                    [
                        'category_id' => $sourceProduct->category_id,
                        'brand_id' => $sourceProduct->brand_id,
                        'unit_id' => $sourceProduct->unit_id,
                        'bin_location_id' => null,
                        'name' => $sourceProduct->name,
                        'stock' => 0,
                        'min_stock' => $sourceProduct->min_stock,
                        'max_stock' => $sourceProduct->max_stock,
                        'cost_price' => $sourceProduct->cost_price,
                        'selling_price' => $sourceProduct->selling_price,
                        'is_fast_moving' => $sourceProduct->is_fast_moving,
                    ],
                );

                $this->stockMovement->increase(
                    $destinationProduct,
                    $item->qty,
                );
            }

            $transfer->update([
                'status' => WarehouseTransferStatus::STATUS_COMPLETED,
                'received_by' => $receivedByUserId,
            ]);

            $transfer->logAudit('completed');
        });
    }
}
