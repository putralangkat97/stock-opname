<?php

use App\Enums\StockOpnameItemStatus;
use App\Enums\StockOpnameStatus;
use App\Models\BinLocation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Rack;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createStockOpnameCountStateWarehouse(): Warehouse
{
    return Warehouse::query()->create([
        'code' => 'TEST-COUNT-STATE-WH-'.str()->random(8),
        'name' => 'Test Count State Warehouse',
    ]);
}

function createStockOpnameCountStateProduct(
    Warehouse $warehouse,
): Product {
    $suffix = str()->random(8);

    $category = Category::query()->create([
        'code' => "TEST-COUNT-CAT-{$suffix}",
        'name' => 'Test Category',
    ]);

    $brand = Brand::query()->create([
        'code' => "TEST-COUNT-BRAND-{$suffix}",
        'name' => 'Test Brand',
    ]);

    $unit = Unit::query()->create([
        'code' => "TEST-COUNT-UNIT-{$suffix}",
        'name' => 'Pieces',
        'symbol' => 'pcs',
    ]);

    $rack = Rack::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-COUNT-RACK-{$suffix}",
    ]);

    $binLocation = BinLocation::query()->create([
        'rack_id' => $rack->id,
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-COUNT-BIN-{$suffix}",
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'unit_id' => $unit->id,
        'warehouse_id' => $warehouse->id,
        'bin_location_id' => $binLocation->id,
        'sku' => "TEST-COUNT-SKU-{$suffix}",
        'name' => 'Test Count Product',
        'stock' => 10,
        'cost_price' => 100,
    ]);
}

function createStockOpnameCountStateFixture(
    StockOpnameStatus $status,
): StockOpnameItem {
    $warehouse = createStockOpnameCountStateWarehouse();
    $product = createStockOpnameCountStateProduct($warehouse);

    $assignedTo = User::factory()->create();

    $stockOpname = StockOpname::query()->create([
        'warehouse_id' => $warehouse->id,
        'assigned_to' => $assignedTo->id,
        'opname_number' => 'TEST-COUNT-STATE-'.str()->random(8),
        'title' => 'Test Count State',
        'start_date' => now()->toDateString(),
        'status' => $status,
        'total_system_qty' => 10,
        'total_physical_qty' => 0,
        'total_variance_qty' => 0,
        'total_variance_value' => 0,
    ]);

    return StockOpnameItem::query()->create([
        'stock_opname_id' => $stockOpname->id,
        'product_id' => $product->id,
        'system_qty' => 10,
        'physical_qty' => null,
        'status' => StockOpnameItemStatus::STATUS_UNCOUNTED,
    ]);
}

it('allows recording a count while stock opname is in progress', function () {
    $item = createStockOpnameCountStateFixture(
        StockOpnameStatus::STATUS_IN_PROGRESS,
    );

    $scanner = User::factory()->create();

    $item->recordCount(
        physicalQty: 12,
        scannedBy: $scanner,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(12);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SURPLUS);

    expect($item->scanned_by)
        ->toBe($scanner->id);

    expect($item->scanned_at)
        ->not->toBeNull();
});

it('does not allow recording a count while stock opname is in draft', function () {
    $item = createStockOpnameCountStateFixture(
        StockOpnameStatus::STATUS_DRAFT,
    );

    $scanner = User::factory()->create();

    expect(fn () => $item->recordCount(
        physicalQty: 12,
        scannedBy: $scanner,
    ))->toThrow(
        RuntimeException::class,
        'Stock opname cannot record counts in its current state.',
    );

    $item->refresh();

    expect($item->physical_qty)->toBeNull();

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_UNCOUNTED);

    expect($item->scanned_by)->toBeNull();

    expect($item->scanned_at)->toBeNull();
});

it('does not allow recounting after stock opname is completed', function () {
    $item = createStockOpnameCountStateFixture(
        StockOpnameStatus::STATUS_COMPLETED,
    );

    $item->update([
        'physical_qty' => 10,
        'status' => StockOpnameItemStatus::STATUS_MATCHED,
    ]);

    $scanner = User::factory()->create();

    expect(fn () => $item->recordCount(
        physicalQty: 15,
        scannedBy: $scanner,
    ))->toThrow(
        RuntimeException::class,
        'Stock opname cannot record counts in its current state.',
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(10);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_MATCHED);

    expect($item->scanned_by)->toBeNull();

    expect($item->scanned_at)->toBeNull();
});

it('does not allow recording a count after stock opname is approved', function () {
    $item = createStockOpnameCountStateFixture(
        StockOpnameStatus::STATUS_APPROVED,
    );

    $scanner = User::factory()->create();

    expect(fn () => $item->recordCount(
        physicalQty: 15,
        scannedBy: $scanner,
    ))->toThrow(
        RuntimeException::class,
        'Stock opname cannot record counts in its current state.',
    );

    $item->refresh();

    expect($item->physical_qty)->toBeNull();

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_UNCOUNTED);

    expect($item->scanned_by)->toBeNull();

    expect($item->scanned_at)->toBeNull();
});
