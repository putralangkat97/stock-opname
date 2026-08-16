<?php

use App\Domain\GoodsReceipt\Services\GoodsReceiptApprovalService;
use App\Enums\GoodsReceiptStatus;
use App\Models\BinLocation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Product;
use App\Models\Rack;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createGoodsReceiptTestProduct(
    int $stock = 10,
    string $sku = 'TEST-GR-SKU-001',
): Product {
    $suffix = str()->random(8);

    $category = Category::query()->create([
        'code' => "TEST-GR-CAT-{$suffix}",
        'name' => 'Test Category',
    ]);

    $brand = Brand::query()->create([
        'code' => "TEST-GR-BRAND-{$suffix}",
        'name' => 'Test Brand',
    ]);

    $unit = Unit::query()->create([
        'code' => "TEST-GR-UNIT-{$suffix}",
        'name' => 'Pieces',
        'symbol' => 'pcs',
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => "TEST-GR-WH-{$suffix}",
        'name' => 'Test Warehouse',
    ]);

    $rack = Rack::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-GR-RACK-{$suffix}",
    ]);

    $binLocation = BinLocation::query()->create([
        'rack_id' => $rack->id,
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-GR-BIN-{$suffix}",
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

function createTestGoodsReceipt(
    Product $product,
    int $qty = 5,
): GoodsReceipt {
    $suffix = str()->random(8);

    $supplier = Supplier::query()->create([
        'code' => "TEST-GR-SUP-{$suffix}",
        'name' => 'Test Supplier',
    ]);

    $user = User::factory()->create();

    $receipt = GoodsReceipt::query()->create([
        'supplier_id' => $supplier->id,
        'warehouse_id' => $product->warehouse_id,
        'received_by' => $user->id,
        'receipt_number' => "TEST-GR-{$suffix}",
        'date' => now()->toDateString(),
        'status' => GoodsReceiptStatus::STATUS_DRAFT,
        'total_amount' => $qty * 100,
    ]);

    GoodsReceiptItem::query()->create([
        'goods_receipt_id' => $receipt->id,
        'product_id' => $product->id,
        'product_sku_snapshot' => $product->sku,
        'product_name_snapshot' => $product->name,
        'qty' => $qty,
        'unit_price' => 100,
        'subtotal' => $qty * 100,
    ]);

    return $receipt;
}

it('increases product stock when a goods receipt is approved', function () {
    $product = createGoodsReceiptTestProduct(10);

    $receipt = createTestGoodsReceipt($product, 5);

    app(GoodsReceiptApprovalService::class)->approve($receipt);

    expect($product->fresh()->stock)->toBe(15);
    expect($receipt->fresh()->status)
        ->toBe(GoodsReceiptStatus::STATUS_RECEIVED);
});
