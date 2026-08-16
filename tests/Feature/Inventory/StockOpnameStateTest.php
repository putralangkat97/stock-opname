<?php

use App\Enums\StockOpnameStatus;
use App\Models\StockOpname;

it('allows only start from the draft state', function () {
    $stockOpname = new StockOpname([
        'status' => StockOpnameStatus::STATUS_DRAFT,
    ]);

    expect($stockOpname->canStart())->toBeTrue();
    expect($stockOpname->canRecordCount())->toBeFalse();
    expect($stockOpname->canComplete())->toBeFalse();
    expect($stockOpname->canApprove())->toBeFalse();
    expect($stockOpname->canReject())->toBeFalse();
});

it('allows counting and completion while in progress', function () {
    $stockOpname = new StockOpname([
        'status' => StockOpnameStatus::STATUS_IN_PROGRESS,
    ]);

    expect($stockOpname->canStart())->toBeFalse();
    expect($stockOpname->canRecordCount())->toBeTrue();
    expect($stockOpname->canComplete())->toBeTrue();
    expect($stockOpname->canApprove())->toBeFalse();
    expect($stockOpname->canReject())->toBeFalse();
});

it('allows approval and recount rejection after completion', function () {
    $stockOpname = new StockOpname([
        'status' => StockOpnameStatus::STATUS_COMPLETED,
    ]);

    expect($stockOpname->canStart())->toBeFalse();
    expect($stockOpname->canRecordCount())->toBeFalse();
    expect($stockOpname->canComplete())->toBeFalse();
    expect($stockOpname->canApprove())->toBeTrue();
    expect($stockOpname->canReject())->toBeTrue();
});

it('locks an approved stock opname from further workflow actions', function () {
    $stockOpname = new StockOpname([
        'status' => StockOpnameStatus::STATUS_APPROVED,
    ]);

    expect($stockOpname->canStart())->toBeFalse();
    expect($stockOpname->canRecordCount())->toBeFalse();
    expect($stockOpname->canComplete())->toBeFalse();
    expect($stockOpname->canApprove())->toBeFalse();
    expect($stockOpname->canReject())->toBeFalse();
});
