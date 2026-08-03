<?php

namespace App\Enums;

enum StockAdjustmentStatus: string
{
    case STATUS_PENDING = "Pending";
    case STATUS_APPROVED = "Approved";
    case STATUS_REJECTED = "Rejected";
}
