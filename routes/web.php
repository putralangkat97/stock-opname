<?php

use App\Http\Controllers\BinLocationController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\GoodsIssueController;
use App\Http\Controllers\GoodsReceiptController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RackController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StockOpnameItemController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseTransferController;
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

    // Warehouse
    Route::resource('warehouse-transfers', WarehouseTransferController::class)
        ->only(['index', 'show', 'store']);
    Route::post('warehouse-transfers/{warehouse_transfer}/mark-in-transit', [WarehouseTransferController::class, 'markInTransit'])
        ->name('warehouse-transfers.mark-in-transit');
    Route::post('warehouse-transfers/{warehouse_transfer}/complete', [WarehouseTransferController::class, 'complete'])
        ->name('warehouse-transfers.complete');
    Route::post('warehouse-transfers/{warehouse_transfer}/reject', [WarehouseTransferController::class, 'reject'])
        ->name('warehouse-transfers.reject');

    // Stock Adjustment
    Route::resource('stock-adjustments', StockAdjustmentController::class)
        ->only(['index', 'show', 'store']);
    Route::post('stock-adjustments/{stock_adjustment}/approve', [StockAdjustmentController::class, 'approve'])
        ->name('stock-adjustments.approve');
    Route::post('stock-adjustments/{stock_adjustment}/reject', [StockAdjustmentController::class, 'reject'])
        ->name('stock-adjustments.reject');

    // Stock Opname

    Route::resource('stock-opnames', StockOpnameController::class)
        ->only(['index', 'show', 'store']);
    Route::post('stock-opnames/{stock_opname}/start', [StockOpnameController::class, 'start'])
        ->name('stock-opnames.start');
    Route::post('stock-opnames/{stock_opname}/complete', [StockOpnameController::class, 'complete'])
        ->name('stock-opnames.complete');
    Route::post('stock-opnames/{stock_opname}/approve', [StockOpnameController::class, 'approve'])
        ->name('stock-opnames.approve');
    Route::post('stock-opnames/{stock_opname}/reject', [StockOpnameController::class, 'reject'])
        ->name('stock-opnames.reject');
    // Item-level: recording a single line's physical count. Not a full resource
    // route — this model only ever needs the one custom action, never plain CRUD.
    Route::post('stock-opname-items/{stock_opname_item}/record-count', [StockOpnameItemController::class, 'recordCount'])
        ->name('stock-opname-items.record-count');
});
