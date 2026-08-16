<?php

namespace App\Actions\WarehouseTransfer;

use App\Domain\WarehouseTransfer\Services\WarehouseTransferDispatchService;
use App\Models\WarehouseTransfer;

final class MarkWarehouseTransferInTransit
{
    public function __construct(
        private readonly WarehouseTransferDispatchService $service,
    ) {}

    public function execute(WarehouseTransfer $transfer): void
    {
        $this->service->dispatch($transfer);
    }
}
