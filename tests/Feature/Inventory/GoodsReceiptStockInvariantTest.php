<?php

use App\Domain\GoodsIssue\Services\GoodsIssueApprovalService;
use App\Enums\GoodsIssueStatus;
use App\Models\BinLocation;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\Product;
use App\Models\Rack;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createGoodsIssueTestProduct(
    int $stock = 10,
    string $sku = 'TEST-GI-SKU-001',
): Product {
    $suffix = str()->random(8);

    $category = Category::query()->create([
        'code' => "TEST-GI-CAT-{$suffix}",
        'name' => 'Test Category',
    ]);

    $brand = Brand::query()->create([
        'code' => "TEST-GI-BRAND-{$suffix}",
        'name' => 'Test Brand',
    ]);

    $unit = Unit::query()->create([
        'code' => "TEST-GI-UNIT-{$suffix}",
        'name' => 'Pieces',
        'symbol' => 'pcs',
    ]);

    $warehouse = Warehouse::query()->create([
        'code' => "TEST-GI-WH-{$suffix}",
        'name' => 'Test Warehouse',
    ]);

    $rack = Rack::query()->create([
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-GI-RACK-{$suffix}",
    ]);

    $binLocation = BinLocation::query()->create([
        'rack_id' => $rack->id,
        'warehouse_id' => $warehouse->id,
        'code' => "TEST-GI-BIN-{$suffix}",
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

function createTestGoodsIssue(
    Product $product,
    int $qty = 5,
): GoodsIssue {
    $suffix = str()->random(8);

    $customer = Customer::query()->create([
        'code' => "TEST-GI-CUSTOMER-{$suffix}",
        'name' => 'Test Customer',
    ]);

    $user = User::factory()->create();

    $issue = GoodsIssue::query()->create([
        'customer_id' => $customer->id,
        'warehouse_id' => $product->warehouse_id,
        'issued_by' => $user->id,
        'issue_number' => "TEST-GI-{$suffix}",
        'date' => now()->toDateString(),
        'status' => GoodsIssueStatus::STATUS_DRAFT,
        'total_amount' => $qty * 100,
    ]);

    GoodsIssueItem::query()->create([
        'goods_issue_id' => $issue->id,
        'product_id' => $product->id,
        'product_sku_snapshot' => $product->sku,
        'product_name_snapshot' => $product->name,
        'qty' => $qty,
        'unit_price' => 100,
        'subtotal' => $qty * 100,
    ]);

    return $issue;
}

it('decreases product stock when a goods issue is approved', function () {
    $product = createGoodsIssueTestProduct(10);

    $issue = createTestGoodsIssue($product, 4);

    app(GoodsIssueApprovalService::class)->approve($issue);

    expect($product->fresh()->stock)->toBe(6);
    expect($issue->fresh()->status)
        ->toBe(GoodsIssueStatus::STATUS_ISSUED);
});
