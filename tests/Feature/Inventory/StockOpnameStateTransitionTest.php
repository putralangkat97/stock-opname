<?php

use App\Enums\StockOpnameStatus;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('transitions a draft stock opname to in progress through its state', function () {
    $stockOpname = StockOpname::query()->create([
        'warehouse_id' => createStockOpnameTestWarehouseForState()->id,
        'assigned_to' => User::factory()->create()->id,
        'opname_number' => 'TEST-STATE-DRAFT-'.str()->random(8),
        'title' => 'Test Stock Opname',
        'start_date' => now()->toDateString(),
        'status' => StockOpnameStatus::STATUS_DRAFT,
        'total_system_qty' => 0,
        'total_physical_qty' => 0,
        'total_variance_qty' => 0,
        'total_variance_value' => 0,
    ]);

    $stockOpname->start();

    expect($stockOpname->fresh()->status)
        ->toBe(StockOpnameStatus::STATUS_IN_PROGRESS);
});

it('transitions a completed stock opname back to in progress when rejected', function () {
    $stockOpname = StockOpname::query()->create([
        'warehouse_id' => createStockOpnameTestWarehouseForState()->id,
        'assigned_to' => User::factory()->create()->id,
        'opname_number' => 'TEST-STATE-COMPLETED-'.str()->random(8),
        'title' => 'Test Stock Opname',
        'start_date' => now()->toDateString(),
        'status' => StockOpnameStatus::STATUS_COMPLETED,
        'total_system_qty' => 10,
        'total_physical_qty' => 12,
        'total_variance_qty' => 2,
        'total_variance_value' => 200,
        'completed_date' => now()->toDateString(),
    ]);

    $stockOpname->reject();

    expect($stockOpname->fresh()->status)
        ->toBe(StockOpnameStatus::STATUS_IN_PROGRESS);
});

it('does not allow an in progress stock opname to be rejected', function () {
    $stockOpname = StockOpname::query()->create([
        'warehouse_id' => createStockOpnameTestWarehouseForState()->id,
        'assigned_to' => User::factory()->create()->id,
        'opname_number' => 'TEST-STATE-IN-PROGRESS-'.str()->random(8),
        'title' => 'Test Stock Opname',
        'start_date' => now()->toDateString(),
        'status' => StockOpnameStatus::STATUS_IN_PROGRESS,
        'total_system_qty' => 10,
        'total_physical_qty' => 10,
        'total_variance_qty' => 0,
        'total_variance_value' => 0,
    ]);

    expect(fn () => $stockOpname->reject())
        ->toThrow(
            RuntimeException::class,
            'Stock opname cannot be rejected in its current state.',
        );
});

it('does not allow an approved stock opname to be started', function () {
    $stockOpname = StockOpname::query()->create([
        'warehouse_id' => createStockOpnameTestWarehouseForState()->id,
        'assigned_to' => User::factory()->create()->id,
        'opname_number' => 'TEST-STATE-APPROVED-'.str()->random(8),
        'title' => 'Test Stock Opname',
        'start_date' => now()->toDateString(),
        'status' => StockOpnameStatus::STATUS_APPROVED,
        'total_system_qty' => 10,
        'total_physical_qty' => 12,
        'total_variance_qty' => 2,
        'total_variance_value' => 200,
        'completed_date' => now()->toDateString(),
        'approved_at' => now(),
    ]);

    expect(fn () => $stockOpname->start())
        ->toThrow(
            RuntimeException::class,
            'Stock opname cannot be started in its current state.',
        );
});

function createStockOpnameTestWarehouseForState(): Warehouse
{
    return Warehouse::query()->create([
        'code' => 'TEST-STATE-WH-'.str()->random(8),
        'name' => 'Test Warehouse',
    ]);
}
