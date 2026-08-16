<?php

use App\Domain\StockMovement\Services\StockMovementService;
use App\Models\BinLocation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Rack;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTestProduct(int $stock = 10): Product
{
    $category = Category::query()->create([
        'code' => 'TEST-CAT',
        'name' => 'Test Category',
    ]);

    $brand = Brand::query()->create([
        'code' => 'TEST-BRAND',
        'name' => 'Test Brand',
    ]);

    $unit = Unit::query()->create([
        'code' => 'PCS',
        'name' => 'Pieces',
        'symbol' => 'pcs',
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => 'TEST-WH',
        'name' => 'Test Warehouse',
    ]);

    $rack = Rack::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => 'RACK-01',
    ]);

    $binLocation = BinLocation::query()->create([
        'rack_id' => $rack->id,
        'warehouse_id' => $warehouse->id,
        'code' => 'BIN-01',
    ]);

    return Product::query()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'unit_id' => $unit->id,
        'warehouse_id' => $warehouse->id,
        'bin_location_id' => $binLocation->id,
        'sku' => 'TEST-SKU-001',
        'name' => 'Test Product',
        'stock' => $stock,
    ]);
}

it('increases product stock', function () {
    $product = createTestProduct(10);

    app(StockMovementService::class)->increase($product, 5);

    expect($product->fresh()->stock)->toBe(15);
});

it('decreases product stock', function () {
    $product = createTestProduct(10);

    app(StockMovementService::class)->decrease($product, 4);

    expect($product->fresh()->stock)->toBe(6);
});

it('rejects a decrease when stock is insufficient', function () {
    $product = createTestProduct(5);

    expect(
        fn () => app(StockMovementService::class)->decrease($product, 6),
    )->toThrow(
        RuntimeException::class,
        "Insufficient stock for {$product->sku}: have 5, need 6.",
    );

    expect($product->fresh()->stock)->toBe(5);
});

it('synchronizes the passed product instance after decreasing stock', function () {
    $product = createTestProduct(10);

    app(StockMovementService::class)->decrease($product, 3);

    expect($product->stock)->toBe(7);
});
