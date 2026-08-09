<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'product_sku_snapshot',
        'product_name_snapshot',
        'qty',
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (StockAdjustmentItem $item) {
            if (
                $item->product_id &&
                ($item->isDirty('product_id') || ! $item->exists)
            ) {
                $product = $item->product ?? Product::find($item->product_id);
                $item->product_sku_snapshot = $product?->sku;
                $item->product_name_snapshot = $product?->name;
            }
        });
    }
}
