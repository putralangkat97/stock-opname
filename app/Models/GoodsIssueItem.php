<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'goods_issue_id',
    'product_id',
    'product_sku_snapshot',
    'product_name_snapshot',
    'qty',
    'unit_price',
    'subtotal',
])]
class GoodsIssueItem extends Model
{
    use HasFactory;

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /** @return BelongsTo */
    public function goodsIssue(): BelongsTo
    {
        return $this->belongsTo(GoodsIssue::class);
    }

    /** @return BelongsTo */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted(): void
    {
        static::saving(function (GoodsIssueItem $item) {
            if (
                $item->product_id &&
                ($item->isDirty('product_id') || ! $item->exists)
            ) {
                $product = $item->product ?? Product::query()->find($item->product_id);
                $item->product_sku_snapshot = $product?->sku;
                $item->product_name_snapshot = $product?->name;
            }

            $item->subtotal = round($item->qty * $item->unit_price, 2);
        });
    }
}
