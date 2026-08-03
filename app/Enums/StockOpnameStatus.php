<?php

namespace App\Enums;

enum StockOpnameStatus: string
{
    case STATUS_DRAFT = "Draft";
    case STATUS_IN_PROGRESS = "In Progress";
    case STATUS_COMPLETED = "Completed";
    case STATUS_APPROVED = "Approved";
    case STATUS_REJECTED = "Rejected";
}
