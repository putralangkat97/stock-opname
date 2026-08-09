<?php

namespace App\Enums;

enum WarehouseTransferStatus: string
{
    case STATUS_PENDING = 'Pending';
    case STATUS_IN_TRANSIT = 'In Transit';
    case STATUS_COMPLETED = 'Completed';
    case STATUS_REJECTED = 'Rejected';
}
