<?php

use App\Domain\StockOpname\Services\StockOpnameCompletionService;
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

function createStockOpnameTestWarehouse(
    string $prefix = 'TEST-OPN-WH',
): Warehouse {
    $suffix = str()->random(8);

    return Warehouse::query()->create([
        'code' => "{$prefix}-{$suffix}",
        'name' => 'Test Warehouse',
    ]);
}

function createStockOpnameTestProduct(
    Warehouse $warehouse,
    int $stock = 10,
    string $sku = 'TEST-OPN-SKU-001',
): Product {
    $suffix = str()->random(8);

    $category = Category::query()->create([
        'code' => "TEST-OPN-CAT-{$suffix}",
        'name' => 'Test Category',
    ]);

    $brand = Brand::query()->create([
        'code' => "TEST-OPN-BRAND-{$suffix}",
        'name' => 'Test Brand',
    ]);

    $unit = Unit::query()->create([
        'code' => "TEST-OPN-UNIT-{$suffix}",
        'name' => 'Pieces',
        'symbol' => 'pcs',
    ]);

    $rack = Rack::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-OPN-RACK-{$suffix}",
    ]);

    $binLocation = BinLocation::query()->create([
        'rack_id' => $rack->id,
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-OPN-BIN-{$suffix}",
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'unit_id' => $unit->id,
        'warehouse_id' => $warehouse->id,
        'bin_location_id' => $binLocation->id,
        'sku' => "{$sku}-{$suffix}",
        'name' => 'Test Product',
        'stock' => $stock,
        'cost_price' => 100,
    ]);
}

function createTestStockOpname(
    Warehouse $warehouse,
    User $assignedTo,
): StockOpname {
    $suffix = str()->random(8);

    return StockOpname::query()->create([
        'warehouse_id' => $warehouse->id,
        'assigned_to' => $assignedTo->id,
        'opname_number' => "TEST-OPN-{$suffix}",
        'title' => 'Test Stock Opname',
        'start_date' => now()->toDateString(),
        'status' => StockOpnameStatus::STATUS_IN_PROGRESS,
        'total_system_qty' => 0,
        'total_physical_qty' => 0,
        'total_variance_qty' => 0,
        'total_variance_value' => 0,
    ]);
}

function createStockOpnameTestItem(
    StockOpname $stockOpname,
    Product $product,
    int $systemQty,
): StockOpnameItem {
    return StockOpnameItem::query()->create([
        'stock_opname_id' => $stockOpname->id,
        'product_id' => $product->id,
        'system_qty' => $systemQty,
        'physical_qty' => null,
        'status' => StockOpnameItemStatus::STATUS_UNCOUNTED,
    ]);
}

it('does not change product stock when a stock opname is completed', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
    );

    $stockOpname = createTestStockOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $item = createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $product,
        systemQty: 10,
    );

    $item->recordCount(
        physicalQty: 13,
        scannedBy: $assignedTo,
    );

    app(StockOpnameCompletionService::class)->complete($stockOpname);

    expect($product->fresh()->stock)->toBe(10);

    expect($stockOpname->fresh()->status)
        ->toBe(StockOpnameStatus::STATUS_COMPLETED);

    expect($stockOpname->fresh()->total_system_qty)
        ->toBe(10);

    expect($stockOpname->fresh()->total_physical_qty)
        ->toBe(13);

    expect($stockOpname->fresh()->total_variance_qty)
        ->toBe(3);

    expect((float) $stockOpname->fresh()->total_variance_value)
        ->toBe(300.0);
});
