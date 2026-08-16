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
use RuntimeException;

uses(RefreshDatabase::class);

function createRecordCountWarehouse(): Warehouse
{
    return Warehouse::query()->create([
        'code' => 'TEST-REC-WH-'.str()->random(8),
        'name' => 'Test Record Count Warehouse',
    ]);
}

function createRecordCountProduct(Warehouse $warehouse): Product
{
    $suffix = str()->random(8);

    $category = Category::query()->create([
        'code' => "TEST-REC-CAT-{$suffix}",
        'name' => 'Test Category',
    ]);

    $brand = Brand::query()->create([
        'code' => "TEST-REC-BRAND-{$suffix}",
        'name' => 'Test Brand',
    ]);

    $unit = Unit::query()->create([
        'code' => "TEST-REC-UNIT-{$suffix}",
        'name' => 'Pieces',
        'symbol' => 'pcs',
    ]);

    $rack = Rack::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-REC-RACK-{$suffix}",
    ]);

    $binLocation = BinLocation::query()->create([
        'rack_id' => $rack->id,
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-REC-BIN-{$suffix}",
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'unit_id' => $unit->id,
        'warehouse_id' => $warehouse->id,
        'bin_location_id' => $binLocation->id,
        'sku' => "TEST-REC-SKU-{$suffix}",
        'name' => 'Test Record Count Product',
        'stock' => 10,
        'cost_price' => 100,
    ]);
}

function createRecordCountOpname(
    Warehouse $warehouse,
    User $assignedTo,
    StockOpnameStatus $status = StockOpnameStatus::STATUS_IN_PROGRESS,
): StockOpname {
    return StockOpname::query()->create([
        'warehouse_id' => $warehouse->id,
        'assigned_to' => $assignedTo->id,
        'opname_number' => 'TEST-REC-OPN-'.str()->random(8),
        'title' => 'Test Record Count Opname',
        'start_date' => now()->toDateString(),
        'status' => $status,
        'total_system_qty' => 0,
        'total_physical_qty' => 0,
        'total_variance_qty' => 0,
        'total_variance_value' => 0,
    ]);
}

function createRecordCountItem(
    StockOpname $stockOpname,
    Product $product,
): StockOpnameItem {
    return StockOpnameItem::query()->create([
        'stock_opname_id' => $stockOpname->id,
        'product_id' => $product->id,
        'system_qty' => 10,
        'physical_qty' => null,
        'status' => StockOpnameItemStatus::STATUS_UNCOUNTED,
    ]);
}

it('records a count through the stock opname aggregate', function () {
    $warehouse = createRecordCountWarehouse();
    $assignedTo = User::factory()->create();
    $scanner = User::factory()->create();

    $product = createRecordCountProduct($warehouse);

    $stockOpname = createRecordCountOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $item = createRecordCountItem(
        stockOpname: $stockOpname,
        product: $product,
    );

    $stockOpname->recordCount(
        item: $item,
        physicalQty: 13,
        scannedBy: $scanner,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(13);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SURPLUS);

    expect($item->scanned_by)
        ->toBe($scanner->id);

    expect($item->scanned_at)
        ->not->toBeNull();
});

it('calculates matched status when recording through the stock opname aggregate', function () {
    $warehouse = createRecordCountWarehouse();
    $assignedTo = User::factory()->create();
    $scanner = User::factory()->create();

    $product = createRecordCountProduct($warehouse);

    $stockOpname = createRecordCountOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $item = createRecordCountItem(
        stockOpname: $stockOpname,
        product: $product,
    );

    $stockOpname->recordCount(
        item: $item,
        physicalQty: 10,
        scannedBy: $scanner,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(10);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_MATCHED);

    expect($item->variance)->toBe(0);
});

it('calculates shortage status when recording through the stock opname aggregate', function () {
    $warehouse = createRecordCountWarehouse();
    $assignedTo = User::factory()->create();
    $scanner = User::factory()->create();

    $product = createRecordCountProduct($warehouse);

    $stockOpname = createRecordCountOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $item = createRecordCountItem(
        stockOpname: $stockOpname,
        product: $product,
    );

    $stockOpname->recordCount(
        item: $item,
        physicalQty: 7,
        scannedBy: $scanner,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(7);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SHORTAGE);

    expect($item->variance)->toBe(-3);
});

it('does not mutate actual product stock when recording a count', function () {
    $warehouse = createRecordCountWarehouse();
    $assignedTo = User::factory()->create();
    $scanner = User::factory()->create();

    $product = createRecordCountProduct($warehouse);

    $stockOpname = createRecordCountOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $item = createRecordCountItem(
        stockOpname: $stockOpname,
        product: $product,
    );

    $stockOpname->recordCount(
        item: $item,
        physicalQty: 15,
        scannedBy: $scanner,
    );

    expect($product->fresh()->stock)->toBe(10);
});

it('rejects recording a count for an item belonging to another stock opname', function () {
    $warehouse = createRecordCountWarehouse();
    $assignedTo = User::factory()->create();
    $scanner = User::factory()->create();

    $product = createRecordCountProduct($warehouse);

    $firstOpname = createRecordCountOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $secondOpname = createRecordCountOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $item = createRecordCountItem(
        stockOpname: $secondOpname,
        product: $product,
    );

    expect(fn () => $firstOpname->recordCount(
        item: $item,
        physicalQty: 13,
        scannedBy: $scanner,
    ))->toThrow(
        RuntimeException::class,
        'Stock opname item does not belong to this stock opname.',
    );

    $item->refresh();

    expect($item->physical_qty)->toBeNull();

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_UNCOUNTED);
});

it('rejects recording a count when the stock opname is not in progress', function (
    StockOpnameStatus $status,
) {
    $warehouse = createRecordCountWarehouse();
    $assignedTo = User::factory()->create();
    $scanner = User::factory()->create();

    $product = createRecordCountProduct($warehouse);

    $stockOpname = createRecordCountOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
        status: $status,
    );

    $item = createRecordCountItem(
        stockOpname: $stockOpname,
        product: $product,
    );

    expect(fn () => $stockOpname->recordCount(
        item: $item,
        physicalQty: 13,
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
})->with([
    'draft' => StockOpnameStatus::STATUS_DRAFT,
    'completed' => StockOpnameStatus::STATUS_COMPLETED,
    'approved' => StockOpnameStatus::STATUS_APPROVED,
]);

it('allows recounting through the stock opname aggregate', function () {
    $warehouse = createRecordCountWarehouse();
    $assignedTo = User::factory()->create();
    $firstScanner = User::factory()->create();
    $secondScanner = User::factory()->create();

    $product = createRecordCountProduct($warehouse);

    $stockOpname = createRecordCountOpname(
        warehouse: $warehouse,
        assignedTo: $assignedTo,
    );

    $item = createRecordCountItem(
        stockOpname: $stockOpname,
        product: $product,
    );

    $stockOpname->recordCount(
        item: $item,
        physicalQty: 8,
        scannedBy: $firstScanner,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(8);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_SHORTAGE);

    $firstScannedAt = $item->scanned_at;

    $stockOpname->recordCount(
        item: $item,
        physicalQty: 10,
        scannedBy: $secondScanner,
    );

    $item->refresh();

    expect($item->physical_qty)->toBe(10);

    expect($item->status)
        ->toBe(StockOpnameItemStatus::STATUS_MATCHED);

    expect($item->scanned_by)
        ->toBe($secondScanner->id);

    expect($item->scanned_at)->not->toBeNull();

    expect($item->scanned_at->greaterThanOrEqualTo($firstScannedAt))
        ->toBeTrue();

    expect($item->variance)->toBe(0);

    expect($product->fresh()->stock)->toBe(10);
});
