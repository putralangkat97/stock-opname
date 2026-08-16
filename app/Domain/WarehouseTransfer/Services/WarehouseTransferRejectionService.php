<?php

namespace App\Domain\WarehouseTransfer\Services;

use App\Domain\StockMovement\Services\StockMovementService;
use App\Enums\WarehouseTransferStatus;
use App\Models\WarehouseTransfer;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class WarehouseTransferRejectionService
{
    public function __construct(
        private readonly StockMovementService $stockMovement,
    ) {}

    public function reject(WarehouseTransfer $transfer): void
    {
        if (! in_array(
            $transfer->status,
            [
                WarehouseTransferStatus::STATUS_PENDING,
                WarehouseTransferStatus::STATUS_IN_TRANSIT,
            ],
            true,
        )) {
            throw new RuntimeException(
                'Only a Pending or In Transit transfer can be rejected.',
            );
        }

        DB::transaction(function () use ($transfer): void {
            if ($transfer->status === WarehouseTransferStatus::STATUS_IN_TRANSIT) {
                foreach ($transfer->items as $item) {
                    $this->stockMovement->increase(
                        $item->product,
                        $item->qty,
                    );
                }
            }

            $transfer->update([
                'status' => WarehouseTransferStatus::STATUS_REJECTED,
            ]);

            $transfer->logAudit('rejected');
        });
    }
}
