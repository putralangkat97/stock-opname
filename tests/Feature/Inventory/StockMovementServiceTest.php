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
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createTestProduct(
    int $stock = 10,
    string $sku = 'TEST-SKU-001',
): Product {
    $suffix = str()->random(8);

    $category = Category::query()->create([
        'code' => "TEST-CAT-{$suffix}",
        'name' => 'Test Category',
    ]);

    $brand = Brand::query()->create([
        'code' => "TEST-BRAND-{$suffix}",
        'name' => 'Test Brand',
    ]);

    $unit = Unit::query()->create([
        'code' => "PCS-{$suffix}",
        'name' => 'Pieces',
        'symbol' => 'pcs',
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => "TEST-WH-{$suffix}",
        'name' => 'Test Warehouse',
    ]);

    $rack = Rack::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => "RACK-01-{$suffix}",
    ]);

    $binLocation = BinLocation::query()->create([
        'rack_id' => $rack->id,
        'warehouse_id' => $warehouse->id,
        'code' => "BIN-01-{$suffix}",
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

it('rolls back all stock changes when a later movement fails', function () {
    $productA = createTestProduct(10);

    // Create a second product with the same related records pattern,
    // but a different SKU.
    $productB = createTestProduct(5);

    expect(fn () => DB::transaction(function () use ($productA, $productB) {
        $stockMovement = app(StockMovementService::class);

        $stockMovement->decrease($productA, 3);

        // This should fail.
        $stockMovement->decrease($productB, 10);
    }))->toThrow(
        RuntimeException::class,
    );

    expect($productA->fresh()->stock)->toBe(10);
    expect($productB->fresh()->stock)->toBe(5);
});
