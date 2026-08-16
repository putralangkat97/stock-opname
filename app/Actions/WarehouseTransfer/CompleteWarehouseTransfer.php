<?php

namespace App\Actions\WarehouseTransfer;

use App\Domain\WarehouseTransfer\Services\WarehouseTransferCompletionService;
use App\Models\WarehouseTransfer;

final class CompleteWarehouseTransfer
{
    public function __construct(
        private readonly WarehouseTransferCompletionService $service,
    ) {}

    public function execute(
        WarehouseTransfer $transfer,
        int $receivedByUserId,
    ): void {
        $this->service->complete(
            $transfer,
            $receivedByUserId,
        );
    }
}
