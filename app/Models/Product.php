<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "category_id",
        "brand_id",
        "unit_id",
        "warehouse_id",
        "bin_location_id",
        "sku",
        "barcode",
        "qr_code",
        "name",
        "stock",
        "min_stock",
        "max_stock",
        "cost_price",
        "selling_price",
        "last_opname_date",
        "is_fast_moving",
        "image_url",
    ];

    protected $casts = [
        "last_opname_date" => "date",
        "is_fast_moving" => "boolean",
        "cost_price" => "decimal:2",
        "selling_price" => "decimal:2",
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function binLocation(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class);
    }

    public function goodsReceiptItems(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function goodsIssueItems(): HasMany
    {
        return $this->hasMany(GoodsIssueItem::class);
    }

    public function warehouseTransferItems(): HasMany
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }

    public function stockAdjustmentItems(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function stockOpnameItems(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    // Computed, not stored — see design notes: "In Stock" / "Low Stock" / "Out of Stock".
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->stock <= 0) {
                    return "Out of Stock";
                }

                return $this->stock <= $this->min_stock
                    ? "Low Stock"
                    : "In Stock";
            },
        );
    }
}
