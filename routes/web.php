<?php

use App\Http\Controllers\BinLocationController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GoodsIssueController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RackController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(["auth"])->group(function () {
    Route::get("/dashboard", function () {
        return Inertia::render("dashboard/index");
    });

    // Master Data
    Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
    Route::resource('brands', BrandController::class)->except(['create', 'edit', 'show']);
    Route::resource('units', UnitController::class)->except(['create', 'edit', 'show']);
    Route::resource('suppliers', SupplierController::class)->except(['create', 'edit', 'show']);
    Route::resource('customers', CustomerController::class)->except(['create', 'edit', 'show']);
    Route::resource('products', ProductController::class)->except(['create', 'edit']);
    Route::resource('warehouses', WarehouseController::class)->except(['create', 'edit']);
    Route::resource('warehouses.racks', RackController::class)->shallow()->except(['index', 'create', 'edit', 'show']);
    Route::resource('racks.bin-locations', BinLocationController::class)->shallow()->except(['index', 'create', 'edit', 'show']);

    // Goods Receipt
    Route::resource('goods-receipts', GoodsReceiptController::class)
        ->except(['create', 'edit', 'destroy']);
    Route::post('goods-receipts/{goods_receipt}/approve', [GoodsReceiptController::class, 'approve'])
        ->name('goods-receipts.approve');
    Route::post('goods-receipts/{goods_receipt}/cancel', [GoodsReceiptController::class, 'cancel'])
        ->name('goods-receipts.cancel');

    // Goods Issue
    Route::resource('goods-issues', GoodsIssueController::class)
        ->except(['create', 'edit', 'destroy']);
    Route::post('goods-issues/{goods_issue}/approve', [GoodsIssueController::class, 'approve'])
        ->name('goods-issues.approve');
    Route::post('goods-issues/{goods_issue}/cancel', [GoodsIssueController::class, 'cancel'])
        ->name('goods-issues.cancel');
});
