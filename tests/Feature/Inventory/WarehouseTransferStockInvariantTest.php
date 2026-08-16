<?php

use App\Domain\WarehouseTransfer\Services\WarehouseTransferCompletionService;
use App\Domain\WarehouseTransfer\Services\WarehouseTransferDispatchService;
use App\Enums\WarehouseTransferStatus;
use App\Models\BinLocation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Rack;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Models\WarehouseTransferItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createWarehouseTransferTestProduct(
    Warehouse $warehouse,
    int $stock = 10,
    string $sku = 'TEST-WT-SKU-001',
): Product {
    $suffix = str()->random(8);

    $category = Category::query()->create([
        'code' => "TEST-WT-CAT-{$suffix}",
        'name' => 'Test Category',
    ]);

    $brand = Brand::query()->create([
        'code' => "TEST-WT-BRAND-{$suffix}",
        'name' => 'Test Brand',
    ]);

    $unit = Unit::query()->create([
        'code' => "TEST-WT-UNIT-{$suffix}",
        'name' => 'Pieces',
        'symbol' => 'pcs',
    ]);

    $rack = Rack::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-WT-RACK-{$suffix}",
    ]);

    $binLocation = BinLocation::query()->create([
        'rack_id' => $rack->id,
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-WT-BIN-{$suffix}",
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
    ]);
}

function createWarehouseTransferTestWarehouse(
    string $prefix = 'TEST-WT-WH',
): Warehouse {
    $suffix = str()->random(8);

    return Warehouse::query()->create([
        'code' => "{$prefix}-{$suffix}",
        'name' => 'Test Warehouse',
    ]);
}

function createTestWarehouseTransfer(
    Product $product,
    Warehouse $toWarehouse,
    int $qty = 4,
): WarehouseTransfer {
    $suffix = str()->random(8);

    $user = User::factory()->create();

    $transfer = WarehouseTransfer::query()->create([
        'from_warehouse_id' => $product->warehouse_id,
        'to_warehouse_id' => $toWarehouse->id,
        'transferred_by' => $user->id,
        'transfer_number' => "TEST-WT-{$suffix}",
        'date' => now()->toDateString(),
        'status' => WarehouseTransferStatus::STATUS_PENDING,
        'notes' => null,
    ]);

    WarehouseTransferItem::query()->create([
        'warehouse_transfer_id' => $transfer->id,
        'product_id' => $product->id,
        'product_sku_snapshot' => $product->sku,
        'product_name_snapshot' => $product->name,
        'qty' => $qty,
    ]);

    return $transfer;
}

it('decreases source product stock when a warehouse transfer is dispatched', function () {
    $fromWarehouse = createWarehouseTransferTestWarehouse('TEST-WT-FROM');
    $toWarehouse = createWarehouseTransferTestWarehouse('TEST-WT-TO');

    $product = createWarehouseTransferTestProduct(
        warehouse: $fromWarehouse,
        stock: 10,
    );

    $transfer = createTestWarehouseTransfer(
        product: $product,
        toWarehouse: $toWarehouse,
        qty: 4,
    );

    app(WarehouseTransferDispatchService::class)->dispatch($transfer);

    expect($product->fresh()->stock)->toBe(6);
    expect($transfer->fresh()->status)
        ->toBe(WarehouseTransferStatus::STATUS_IN_TRANSIT);
});

it('creates and increases destination product stock when a warehouse transfer is completed', function () {
    $fromWarehouse = createWarehouseTransferTestWarehouse('TEST-WT-FROM');
    $toWarehouse = createWarehouseTransferTestWarehouse('TEST-WT-TO');

    $sourceProduct = createWarehouseTransferTestProduct(
        warehouse: $fromWarehouse,
        stock: 10,
    );

    $transfer = createTestWarehouseTransfer(
        product: $sourceProduct,
        toWarehouse: $toWarehouse,
        qty: 4,
    );

    // Dispatch first: Pending -> In Transit
    app(WarehouseTransferDispatchService::class)->dispatch($transfer);

    $receivedBy = User::factory()->create();

    // The destination product should not exist before completion.
    expect(
        Product::query()
            ->where('sku', $sourceProduct->sku)
            ->where('warehouse_id', $toWarehouse->id)
            ->exists(),
    )->toBeFalse();

    app(WarehouseTransferCompletionService::class)->complete(
        $transfer,
        $receivedBy->id,
    );

    $destinationProduct = Product::query()
        ->where('sku', $sourceProduct->sku)
        ->where('warehouse_id', $toWarehouse->id)
        ->first();

    expect($destinationProduct)->not->toBeNull();
    expect($destinationProduct->stock)->toBe(4);
    expect($destinationProduct->name)->toBe($sourceProduct->name);
    expect($destinationProduct->category_id)->toBe($sourceProduct->category_id);
    expect($destinationProduct->brand_id)->toBe($sourceProduct->brand_id);
    expect($destinationProduct->unit_id)->toBe($sourceProduct->unit_id);

    expect($transfer->fresh()->status)
        ->toBe(WarehouseTransferStatus::STATUS_COMPLETED);

    expect($transfer->fresh()->received_by)->toBe($receivedBy->id);

    // Source stock was already decreased during dispatch.
    expect($sourceProduct->fresh()->stock)->toBe(6);
});

it('increases existing destination product stock when a warehouse transfer is completed', function () {
    $fromWarehouse = createWarehouseTransferTestWarehouse('TEST-WT-FROM');
    $toWarehouse = createWarehouseTransferTestWarehouse('TEST-WT-TO');

    $sourceProduct = createWarehouseTransferTestProduct(
        warehouse: $fromWarehouse,
        stock: 10,
    );

    $destinationRack = Rack::query()->create([
        'warehouse_id' => $toWarehouse->id,
        'code' => 'TEST-WT-DEST-RACK-'.str()->random(8),
    ]);

    $destinationBin = BinLocation::query()->create([
        'rack_id' => $destinationRack->id,
        'warehouse_id' => $toWarehouse->id,
        'code' => 'TEST-WT-DEST-BIN-'.str()->random(8),
    ]);

    $destinationProduct = Product::query()->create([
        'category_id' => $sourceProduct->category_id,
        'brand_id' => $sourceProduct->brand_id,
        'unit_id' => $sourceProduct->unit_id,
        'warehouse_id' => $toWarehouse->id,
        'bin_location_id' => $destinationBin->id,
        'sku' => $sourceProduct->sku,
        'name' => $sourceProduct->name,
        'stock' => 7,
    ]);

    $transfer = createTestWarehouseTransfer(
        product: $sourceProduct,
        toWarehouse: $toWarehouse,
        qty: 4,
    );

    app(WarehouseTransferDispatchService::class)->dispatch($transfer);

    $receivedBy = User::factory()->create();

    app(WarehouseTransferCompletionService::class)->complete(
        $transfer,
        $receivedBy->id,
    );

    expect(
        Product::query()
            ->where('sku', $sourceProduct->sku)
            ->where('warehouse_id', $toWarehouse->id)
            ->count(),
    )->toBe(1);

    expect($destinationProduct->fresh()->stock)->toBe(11);
    expect($transfer->fresh()->status)
        ->toBe(WarehouseTransferStatus::STATUS_COMPLETED);
    expect($sourceProduct->fresh()->stock)->toBe(6);
});
