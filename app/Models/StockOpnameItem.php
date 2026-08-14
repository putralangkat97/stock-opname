<?php

namespace App\Models;

use App\Enums\StockOpnameItemStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'stock_opname_id',
    'product_id',
    'scanned_by',
    'product_sku_snapshot',
    'product_name_snapshot',
    'system_qty',
    'physical_qty',
    'scanned_at',
    'notes',
    'status',
])]
class StockOpnameItem extends Model
{
    use HasFactory;

    protected $casts = [
        'scanned_at' => 'datetime',
        'status' => StockOpnameItemStatus::class,
    ];

    /** @return BelongsTo */
    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    /** @return BelongsTo */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo */
    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    /**
     * Record a physical count for this line — the only way physical_qty
     * should ever be set (keeps status/scanned_at/scanned_by consistent).
     *
     * @param int $physicalQty
     * @param User $scannedBy
     *
     * @return void
     */
    public function recordCount(int $physicalQty, User $scannedBy): void
    {
        $status = match (true) {
            $physicalQty === $this->system_qty => StockOpnameItemStatus::STATUS_MATCHED,
            $physicalQty > $this->system_qty => StockOpnameItemStatus::STATUS_SURPLUS,
            default => StockOpnameItemStatus::STATUS_SHORTAGE,
        };

        $this->update([
            'physical_qty' => $physicalQty,
            'scanned_by' => $scannedBy->id,
            'scanned_at' => now(),
            'status' => $status,
        ]);
    }

    // Computed, not stored.
    protected function variance(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->physical_qty === null
                ? null
                : $this->physical_qty - $this->system_qty,
        );
    }

    protected function varianceValue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->variance === null
                ? null
                : $this->variance * $this->product->cost_price,
        );
    }

    protected static function booted(): void
    {
        static::saving(function (StockOpnameItem $item) {
            if ($item->product_id && ! $item->exists) {
                $product = $item->product ?? Product::query()->find($item->product_id);
                $item->product_sku_snapshot = $product?->sku;
                $item->product_name_snapshot = $product?->name;

                // Snapshot system_qty from the product's current stock at the
                // moment this line is added to the opname, if not already set.
                if (! isset($item->system_qty)) {
                    $item->system_qty = $product?->stock ?? 0;
                }
            }
        });
    }
}
