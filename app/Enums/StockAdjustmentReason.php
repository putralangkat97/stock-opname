<?php

namespace App\Enums;

enum StockAdjustmentReason: string
{
    case DAMAGED = "Damaged";
    case EXPIRED = "Expired";
    case LOST = "Lost";
    case FOUND = "Found";
    case CORRECTION = "Correction";
}
