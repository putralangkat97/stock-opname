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

it('aggregates multiple stock opname items correctly when completing', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $firstProduct = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-COMPLETE-FIRST',
    );

    $secondProduct = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 20,
        sku: 'TEST-SO-COMPLETE-SECOND',
    );

    $stockOpname = createTestStockOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $firstItem = createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $firstProduct,
        systemQty: 10,
    );

    $secondItem = createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $secondProduct,
        systemQty: 20,
    );

    $firstItem->recordCount(
        physicalQty: 13,
        scannedBy: $assignedTo,
    );

    $secondItem->recordCount(
        physicalQty: 17,
        scannedBy: $assignedTo,
    );

    app(StockOpnameCompletionService::class)->complete($stockOpname);

    $stockOpname->refresh();

    // First item: +3
    // Second item: -3
    // Total variance: 0
    expect($stockOpname->total_system_qty)
        ->toBe(30);

    expect($stockOpname->total_physical_qty)
        ->toBe(30);

    expect($stockOpname->total_variance_qty)
        ->toBe(0);

    // First:  3 × 100 = +300
    // Second: -3 × 100 = -300
    // Total:             0
    expect((float) $stockOpname->total_variance_value)
        ->toBe(0.0);

    expect($stockOpname->status)
        ->toBe(StockOpnameStatus::STATUS_COMPLETED);

    expect($stockOpname->completed_date)
        ->not->toBeNull();

    // Stock Opname must never mutate actual product stock.
    expect($firstProduct->fresh()->stock)
        ->toBe(10);

    expect($secondProduct->fresh()->stock)
        ->toBe(20);
});


it('calculates total variance value correctly using each product cost price', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $firstProduct = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-VALUE-FIRST',
    );

    $firstProduct->update([
        'cost_price' => 100,
    ]);

    $secondProduct = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 20,
        sku: 'TEST-SO-VALUE-SECOND',
    );

    $secondProduct->update([
        'cost_price' => 250,
    ]);

    $stockOpname = createTestStockOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $firstItem = createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $firstProduct,
        systemQty: 10,
    );

    $secondItem = createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $secondProduct,
        systemQty: 20,
    );

    // First item: +3 × 100 = +300
    $firstItem->recordCount(
        physicalQty: 13,
        scannedBy: $assignedTo,
    );

    // Second item: -2 × 250 = -500
    $secondItem->recordCount(
        physicalQty: 18,
        scannedBy: $assignedTo,
    );

    app(StockOpnameCompletionService::class)->complete($stockOpname);

    $stockOpname->refresh();

    expect($stockOpname->total_system_qty)
        ->toBe(30);

    expect($stockOpname->total_physical_qty)
        ->toBe(31);

    expect($stockOpname->total_variance_qty)
        ->toBe(1);

    // +300 - 500 = -200
    expect((float) $stockOpname->total_variance_value)
        ->toBe(-200.0);

    expect($stockOpname->status)
        ->toBe(StockOpnameStatus::STATUS_COMPLETED);

    // Actual product stock must remain unchanged.
    expect($firstProduct->fresh()->stock)
        ->toBe(10);

    expect($secondProduct->fresh()->stock)
        ->toBe(20);
});


it('rejects completing a stock opname when an item has not been counted', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-INCOMPLETE',
    );

    $stockOpname = createTestStockOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $product,
        systemQty: 10,
    );

    expect(
        fn () => app(StockOpnameCompletionService::class)
            ->complete($stockOpname),
    )->toThrow(
        RuntimeException::class,
        'All lines must be counted before completing the opname.',
    );

    $stockOpname->refresh();

    expect($stockOpname->status)
        ->toBe(StockOpnameStatus::STATUS_IN_PROGRESS);

    expect($stockOpname->completed_date)
        ->toBeNull();

    expect($stockOpname->total_system_qty)
        ->toBe(0);

    expect($stockOpname->total_physical_qty)
        ->toBe(0);

    expect($stockOpname->total_variance_qty)
        ->toBe(0);

    expect((float) $stockOpname->total_variance_value)
        ->toBe(0.0);

    // Actual stock must remain untouched.
    expect($product->fresh()->stock)
        ->toBe(10);
});


it('does not complete an already completed stock opname', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-ALREADY-COMPLETED',
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
        physicalQty: 12,
        scannedBy: $assignedTo,
    );

    app(StockOpnameCompletionService::class)->complete($stockOpname);

    $stockOpname->refresh();

    $completedDate = $stockOpname->completed_date;

    expect($stockOpname->status)
        ->toBe(StockOpnameStatus::STATUS_COMPLETED);

    expect($stockOpname->total_variance_qty)
        ->toBe(2);

    expect((float) $stockOpname->total_variance_value)
        ->toBe(200.0);

    // Attempting completion again should be rejected.
    expect(
        fn () => app(StockOpnameCompletionService::class)
            ->complete($stockOpname),
    )->toThrow(
        RuntimeException::class,
        'Stock opname cannot be completed in its current state.',
    );

    $stockOpname->refresh();

    expect($stockOpname->status)
        ->toBe(StockOpnameStatus::STATUS_COMPLETED);

    expect($stockOpname->completed_date)
        ->toEqual($completedDate);

    // Stock must still be untouched.
    expect($product->fresh()->stock)
        ->toBe(10);
});


it('does not change product stock when completing a stock opname with mixed variances', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $matchedProduct = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-MIXED-MATCHED',
    );

    $surplusProduct = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 20,
        sku: 'TEST-SO-MIXED-SURPLUS',
    );

    $shortageProduct = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 30,
        sku: 'TEST-SO-MIXED-SHORTAGE',
    );

    $stockOpname = createTestStockOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $matchedItem = createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $matchedProduct,
        systemQty: 10,
    );

    $surplusItem = createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $surplusProduct,
        systemQty: 20,
    );

    $shortageItem = createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $shortageProduct,
        systemQty: 30,
    );

    $matchedItem->recordCount(
        physicalQty: 10,
        scannedBy: $assignedTo,
    );

    $surplusItem->recordCount(
        physicalQty: 25,
        scannedBy: $assignedTo,
    );

    $shortageItem->recordCount(
        physicalQty: 27,
        scannedBy: $assignedTo,
    );

    app(StockOpnameCompletionService::class)->complete($stockOpname);

    $stockOpname->refresh();

    expect($stockOpname->total_system_qty)
        ->toBe(60);

    expect($stockOpname->total_physical_qty)
        ->toBe(62);

    expect($stockOpname->total_variance_qty)
        ->toBe(2);

    // +0 +5 -3 = +2
    expect((float) $stockOpname->total_variance_value)
        ->toBe(200.0);

    expect($stockOpname->status)
        ->toBe(StockOpnameStatus::STATUS_COMPLETED);

    // Completion is reporting/reconciliation only.
    // It must not modify actual inventory stock.
    expect($matchedProduct->fresh()->stock)
        ->toBe(10);

    expect($surplusProduct->fresh()->stock)
        ->toBe(20);

    expect($shortageProduct->fresh()->stock)
        ->toBe(30);
});

it('snapshots product identity and system quantity when a stock opname item is created', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 25,
        sku: 'TEST-SO-SNAPSHOT',
    );

    $stockOpname = createTestStockOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $item = StockOpnameItem::query()->create([
        'stock_opname_id' => $stockOpname->id,
        'product_id' => $product->id,
        'physical_qty' => null,
        'status' => StockOpnameItemStatus::STATUS_UNCOUNTED,
    ]);

    $item->refresh();

    expect($item->product_sku_snapshot)
        ->toBe($product->sku);

    expect($item->product_name_snapshot)
        ->toBe($product->name);

    expect($item->system_qty)
        ->toBe(25);

    expect($item->physical_qty)
        ->toBeNull();

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_UNCOUNTED);

    expect($product->fresh()->stock)
        ->toBe(25);
});

it('preserves stock opname snapshots when the product changes later', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 25,
        sku: 'TEST-SO-HISTORY',
    );

    $originalSku = $product->sku;
    $originalName = $product->name;
    $originalStock = $product->stock;

    $stockOpname = createTestStockOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $item = StockOpnameItem::query()->create([
        'stock_opname_id' => $stockOpname->id,
        'product_id' => $product->id,
        'physical_qty' => null,
        'status' => StockOpnameItemStatus::STATUS_UNCOUNTED,
    ]);

    // Product changes after the opname line was created.
    $product->update([
        'sku' => 'TEST-SO-HISTORY-CHANGED',
        'name' => 'Changed Product Name',
        'stock' => 40,
    ]);

    $item->refresh();

    // Historical snapshot must remain unchanged.
    expect($item->product_sku_snapshot)
        ->toBe($originalSku);

    expect($item->product_name_snapshot)
        ->toBe($originalName);

    expect($item->system_qty)
        ->toBe($originalStock);

    // The relation reflects the current product.
    expect($item->product->fresh()->sku)
        ->toBe('TEST-SO-HISTORY-CHANGED');

    expect($item->product->fresh()->name)
        ->toBe('Changed Product Name');

    expect($item->product->fresh()->stock)
        ->toBe(40);
});

it('does not overwrite an explicitly provided system quantity when creating a stock opname item', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 25,
        sku: 'TEST-SO-EXPLICIT-QTY',
    );

    $stockOpname = createTestStockOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $item = StockOpnameItem::query()->create([
        'stock_opname_id' => $stockOpname->id,
        'product_id' => $product->id,
        'system_qty' => 20,
        'physical_qty' => null,
        'status' => StockOpnameItemStatus::STATUS_UNCOUNTED,
    ]);

    $item->refresh();

    expect($item->system_qty)
        ->toBe(20);

    expect($item->product_sku_snapshot)
        ->toBe($product->sku);

    expect($item->product_name_snapshot)
        ->toBe($product->name);

    expect($product->fresh()->stock)
        ->toBe(25);
});

it('rejects completing a stock opname when an item is still uncounted', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-COMPLETE-UNCOUNTED',
    );

    $stockOpname = createTestStockOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $product,
        systemQty: 10,
    );

    expect(
        fn () => app(StockOpnameCompletionService::class)
            ->complete($stockOpname),
    )->toThrow(
        RuntimeException::class,
    );

    expect($stockOpname->fresh()->status)
        ->toBe(StockOpnameStatus::STATUS_IN_PROGRESS);

    expect($product->fresh()->stock)
        ->toBe(10);
});

it('allows completing a stock opname when all items have been counted', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();
    $scannedBy = User::factory()->create();

    $product = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-COMPLETE-COUNTED',
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

    app(StockOpnameCompletionService::class)
        ->complete($stockOpname);

    expect($stockOpname->fresh()->status)
        ->toBe(StockOpnameStatus::STATUS_COMPLETED);

    expect($product->fresh()->stock)
        ->toBe(10);
});

it('allows completing a stock opname with shortage and surplus items when all are counted', function () {
    $warehouse = createStockOpnameTestWarehouse();

    $assignedTo = User::factory()->create();
    $scannedBy = User::factory()->create();

    $surplusProduct = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 10,
        sku: 'TEST-SO-COMPLETE-SURPLUS',
    );

    $shortageProduct = createStockOpnameTestProduct(
        warehouse: $warehouse,
        stock: 20,
        sku: 'TEST-SO-COMPLETE-SHORTAGE',
    );

    $stockOpname = createTestStockOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $surplusItem = createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $surplusProduct,
        systemQty: 10,
    );

    $shortageItem = createStockOpnameTestItem(
        stockOpname: $stockOpname,
        product: $shortageProduct,
        systemQty: 20,
    );

    $surplusItem->recordCount(
        physicalQty: 13,
        scannedBy: $scannedBy,
    );

    $shortageItem->recordCount(
        physicalQty: 17,
        scannedBy: $scannedBy,
    );

    app(StockOpnameCompletionService::class)
        ->complete($stockOpname);

    $stockOpname->refresh();

    expect($stockOpname->status)
        ->toBe(StockOpnameStatus::STATUS_COMPLETED);

    expect($stockOpname->total_system_qty)
        ->toBe(30);

    expect($stockOpname->total_physical_qty)
        ->toBe(30);

    expect($stockOpname->total_variance_qty)
        ->toBe(0);

    // Stock Opname records the physical reality but does not mutate
    // Product::stock.
    expect($surplusProduct->fresh()->stock)
        ->toBe(10);

    expect($shortageProduct->fresh()->stock)
        ->toBe(20);
});
