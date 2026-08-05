<?php

namespace App\Enums;

enum StockAdjustmentType: string
{
    case TYPE_IN = 'IN';
    case TYPE_OUT = 'OUT';
}
