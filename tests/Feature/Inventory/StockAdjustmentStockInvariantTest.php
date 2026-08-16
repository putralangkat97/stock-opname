<?php

use App\Domain\StockAdjustment\Services\StockAdjustmentApprovalService;
use App\Enums\StockAdjustmentReason;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockAdjustmentType;
use App\Models\BinLocation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Rack;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createStockAdjustmentTestProduct(
    int $stock = 10,
    string $sku = 'TEST-SA-SKU-001',
): Product {
    $suffix = str()->random(8);

    $category = Category::query()->create([
        'code' => "TEST-SA-CAT-{$suffix}",
        'name' => 'Test Category',
    ]);

    $brand = Brand::query()->create([
        'code' => "TEST-SA-BRAND-{$suffix}",
        'name' => 'Test Brand',
    ]);

    $unit = Unit::query()->create([
        'code' => "TEST-SA-UNIT-{$suffix}",
        'name' => 'Pieces',
        'symbol' => 'pcs',
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => "TEST-SA-WH-{$suffix}",
        'name' => 'Test Warehouse',
    ]);

    $rack = Rack::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-SA-RACK-{$suffix}",
    ]);

    $binLocation = BinLocation::query()->create([
        'rack_id' => $rack->id,
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-SA-BIN-{$suffix}",
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'unit_id' => $unit->id,
        'warehouse_id' => $warehouse->id,
        'bin_location_id' => $binLocation->id,
        'sku' => $sku,
        'name' => 'Test Product',
        'stock' => $stock,
    ]);
}

function createTestStockAdjustment(
    Product $product,
    StockAdjustmentType $type,
    int $qty = 5,
): StockAdjustment {
    $suffix = str()->random(8);

    $user = User::factory()->create();

    $adjustment = StockAdjustment::query()->create([
        'warehouse_id' => $product->warehouse_id,
        'adjusted_by' => $user->id,
        'adjustment_number' => "TEST-SA-{$suffix}",
        'type' => $type->value,
        'reason' => StockAdjustmentReason::CORRECTION->value,
        'date' => now()->toDateString(),
        'status' => StockAdjustmentStatus::STATUS_PENDING,
    ]);

    StockAdjustmentItem::query()->create([
        'stock_adjustment_id' => $adjustment->id,
        'product_id' => $product->id,
        'product_sku_snapshot' => $product->sku,
        'product_name_snapshot' => $product->name,
        'qty' => $qty,
    ]);

    return $adjustment;
}

it('increases product stock when an inbound stock adjustment is approved', function () {
    $product = createStockAdjustmentTestProduct(10);

    $adjustment = createTestStockAdjustment(
        $product,
        StockAdjustmentType::TYPE_IN,
        5,
    );

    app(StockAdjustmentApprovalService::class)->approve($adjustment);

    expect($product->fresh()->stock)->toBe(15);
    expect($adjustment->fresh()->status)
        ->toBe(StockAdjustmentStatus::STATUS_APPROVED);
});

it('decreases product stock when an outbound stock adjustment is approved', function () {
    $product = createStockAdjustmentTestProduct(10);

    $adjustment = createTestStockAdjustment(
        $product,
        StockAdjustmentType::TYPE_OUT,
        4,
    );

    app(StockAdjustmentApprovalService::class)->approve($adjustment);

    expect($product->fresh()->stock)->toBe(6);
    expect($adjustment->fresh()->status)
        ->toBe(StockAdjustmentStatus::STATUS_APPROVED);
});
