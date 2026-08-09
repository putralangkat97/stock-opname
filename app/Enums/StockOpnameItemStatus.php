<?php

namespace App\Enums;

enum StockOpnameItemStatus: string
{
    case STATUS_MATCHED = 'Matched';
    case STATUS_SURPLUS = 'Surplus';
    case STATUS_SHORTAGE = 'Shortage';
    case STATUS_UNCOUNTED = 'Uncounted';
}
