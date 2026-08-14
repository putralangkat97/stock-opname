<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['rack_id', 'warehouse_id', 'code', 'capacity'])]
class BinLocation extends Model
{
    use HasFactory, SoftDeletes;

    /** @return BelongsTo */
    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    /** @return BelongsTo */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** @return HasMany */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted(): void
    {
        // Always derive warehouse_id from the parent rack on create/update,
        // so the denormalized column can never drift out of sync.
        static::saving(function (BinLocation $bin) {
            if ($bin->rack_id && $bin->isDirty('rack_id')) {
                $bin->warehouse_id = Rack::query()->find($bin->rack_id)?->warehouse_id;
            }
        });
    }
}
