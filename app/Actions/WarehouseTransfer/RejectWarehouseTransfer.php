<?php

namespace App\Actions\WarehouseTransfer;

use App\Domain\WarehouseTransfer\Services\WarehouseTransferRejectionService;
use App\Models\WarehouseTransfer;

final class RejectWarehouseTransfer
{
    public function __construct(
        private readonly WarehouseTransferRejectionService $service,
    ) {}

    public function execute(WarehouseTransfer $transfer): void
    {
        $this->service->reject($transfer);
    }
}
