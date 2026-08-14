<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'warehouse_transfer_id',
    'product_id',
    'product_sku_snapshot',
    'product_name_snapshot',
    'qty',
])]
class WarehouseTransferItem extends Model
{
    use HasFactory;

    /** @return BelongsTo */
    public function warehouseTransfer(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransfer::class);
    }

    /** @return BelongsTo */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (WarehouseTransferItem $item) {
            if (
                $item->product_id &&
                ($item->isDirty('product_id') || ! $item->exists)
            ) {
                $product = $item->product ?? Product::query()->find($item->product_id);
                $item->product_sku_snapshot = $product?->sku;
                $item->product_name_snapshot = $product?->name;
            }
        });
    }
}
