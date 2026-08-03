<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rack extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ["warehouse_id", "code", "zone"];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function binLocations(): HasMany
    {
        return $this->hasMany(BinLocation::class);
    }

    protected static function booted(): void
    {
        // Keep every child bin's denormalized warehouse_id in sync if a rack
        // is ever reassigned to a different warehouse.
        static::updated(function (Rack $rack) {
            if ($rack->wasChanged("warehouse_id")) {
                $rack
                    ->binLocations()
                    ->update(["warehouse_id" => $rack->warehouse_id]);
            }
        });
    }
}
