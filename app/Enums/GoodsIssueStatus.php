<?php

namespace App\Enums;

enum GoodsIssueStatus: string
{
    case STATUS_DRAFT = "Draft";
    case STATUS_ISSUED = "Issued";
    case STATUS_CANCELLED = "Cancelled";
}
