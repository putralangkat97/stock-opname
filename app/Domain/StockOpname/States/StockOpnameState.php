<?php

namespace App\Domain\StockOpname\States;

use App\Models\StockOpname;

interface StockOpnameState
{
    public function canStart(StockOpname $stockOpname): bool;

    public function start(StockOpname $stockOpname): void;

    public function canRecordCount(StockOpname $stockOpname): bool;

    public function canComplete(StockOpname $stockOpname): bool;

    public function canApprove(StockOpname $stockOpname): bool;

    public function canReject(StockOpname $stockOpname): bool;

    public function reject(StockOpname $stockOpname): void;
}
