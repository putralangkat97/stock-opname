<?php

namespace App\Enums;

enum GoodsReceiptStatus: string
{
    case STATUS_DRAFT = "Draft";
    case STATUS_RECEIVED = "Received";
    case STATUS_CANCELLED = "Cancelled";
}
