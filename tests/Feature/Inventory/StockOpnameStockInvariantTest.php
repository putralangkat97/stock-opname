<?php

use App\Domain\StockOpname\Services\StockOpnameCompletionService;
use App\Domain\StockOpname\Services\StockOpnameVarianceService;
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

it('marks a stock opname item as matched when physical quantity equals system quantity', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-MATCHED',
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

    $item->update([
        'physical_qty' => 10,
        'status' => StockOpnameItemStatus::STATUS_MATCHED,
    ]);

    $item->refresh();

    expect($item->physical_qty)->toBe(10);
    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_MATCHED);

    expect($product->fresh()->stock)->toBe(10);
});

it('marks a stock opname item as surplus when physical quantity is greater than system quantity', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-SURPLUS',
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

    $item->update([
        'physical_qty' => 12,
        'status' => StockOpnameItemStatus::STATUS_SURPLUS,
    ]);

    $item->refresh();

    expect($item->physical_qty)->toBe(12);
    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SURPLUS);

    expect($item->physical_qty - $item->system_qty)->toBe(2);

    expect($product->fresh()->stock)->toBe(10);
});

it('marks a stock opname item as shortage when physical quantity is less than system quantity', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-SHORTAGE',
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

    $item->update([
        'physical_qty' => 7,
        'status' => StockOpnameItemStatus::STATUS_SHORTAGE,
    ]);

    $item->refresh();

    expect($item->physical_qty)->toBe(7);
    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SHORTAGE);

    expect($item->physical_qty - $item->system_qty)->toBe(-3);

    expect($product->fresh()->stock)->toBe(10);
});

it('keeps a stock opname item uncounted when physical quantity is null', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-UNCOUNTED',
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

    $item->refresh();

    expect($item->physical_qty)->toBeNull();
    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_UNCOUNTED);

    expect($product->fresh()->stock)->toBe(10);
});

it('calculates a matched stock opname item', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-VAR-MATCHED',
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

    $item->update([
        'physical_qty' => 10,
    ]);

    app(StockOpnameVarianceService::class)->calculate($item);

    $item->refresh();

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_MATCHED);

    expect($item->variance)->toBe(0);

    expect($product->fresh()->stock)->toBe(10);
});

it('calculates a surplus stock opname item', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-VAR-SURPLUS',
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

    $item->update([
        'physical_qty' => 13,
    ]);

    app(StockOpnameVarianceService::class)->calculate($item);

    $item->refresh();

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SURPLUS);

    expect($item->variance)->toBe(3);

    expect($product->fresh()->stock)->toBe(10);
});

it('calculates a shortage stock opname item', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-VAR-SHORTAGE',
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

    $item->update([
        'physical_qty' => 7,
    ]);

    app(StockOpnameVarianceService::class)->calculate($item);

    $item->refresh();

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SHORTAGE);

    expect($item->variance)->toBe(-3);

    expect($product->fresh()->stock)->toBe(10);
});

it('marks an uncounted stock opname item as uncounted', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-VAR-UNCOUNTED',
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

    app(StockOpnameVarianceService::class)->calculate($item);

    $item->refresh();

    expect($item->physical_qty)->toBeNull();

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_UNCOUNTED);

    expect($product->fresh()->stock)->toBe(10);
});

it('records a matched physical count through the domain method', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();
    $scannedBy = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-RECORD-MATCHED',
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
        physicalQty: 10,
        scannedBy: $scannedBy,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(10);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_MATCHED);

    expect($item->scanned_by)->toBe($scannedBy->id);

    expect($item->scanned_at)->not->toBeNull();

    expect($product->fresh()->stock)->toBe(10);
});

it('records a surplus physical count through the domain method', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();
    $scannedBy = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-RECORD-SURPLUS',
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
        scannedBy: $scannedBy,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(13);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SURPLUS);

    expect($item->scanned_by)->toBe($scannedBy->id);

    expect($item->scanned_at)->not->toBeNull();

    expect($item->variance)->toBe(3);

    expect($product->fresh()->stock)->toBe(10);
});

it('records a shortage physical count through the domain method', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();
    $scannedBy = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-RECORD-SHORTAGE',
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
        physicalQty: 7,
        scannedBy: $scannedBy,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(7);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SHORTAGE);

    expect($item->scanned_by)->toBe($scannedBy->id);

    expect($item->scanned_at)->not->toBeNull();

    expect($item->variance)->toBe(-3);

    expect($product->fresh()->stock)->toBe(10);
});

it('updates an existing stock opname count consistently when recounting', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();
    $firstScanner = User::factory()->create();
    $secondScanner = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-RECOUNT',
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

    // First count: shortage.
    $item->recordCount(
        physicalQty: 8,
        scannedBy: $firstScanner,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(8);
    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SHORTAGE);
    expect($item->scanned_by)->toBe($firstScanner->id);
    expect($item->scanned_at)->not->toBeNull();

    $firstScannedAt = $item->scanned_at;

    // Second count: now matched.
    $item->recordCount(
        physicalQty: 10,
        scannedBy: $secondScanner,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(10);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_MATCHED);

    expect($item->scanned_by)->toBe($secondScanner->id);

    expect($item->scanned_at)->not->toBeNull();

    expect($item->scanned_at->greaterThanOrEqualTo($firstScannedAt))
        ->toBeTrue();

    expect($item->variance)->toBe(0);

    // Recounting must still never mutate actual product stock.
    expect($product->fresh()->stock)->toBe(10);
});
