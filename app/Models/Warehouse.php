<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "code",
        "name",
        "location",
        "manager",
        "phone",
        "total_capacity",
    ];

    public function racks(): HasMany
    {
        return $this->hasMany(Rack::class);
    }

    // Denormalized direct relation (see bin_locations migration note) — same rows
    // you'd get by going through racks, kept here for convenient eager loading.
    public function binLocations(): HasMany
    {
        return $this->hasMany(BinLocation::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // Users with explicit access to this warehouse (Warehouse Admin / Supervisor scoping).
    // Super Admins bypass this check entirely in the Policy layer — they are not expected
    // to appear in this pivot for every warehouse.
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, "warehouse_user");
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function goodsIssues(): HasMany
    {
        return $this->hasMany(GoodsIssue::class);
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function stockOpnames(): HasMany
    {
        return $this->hasMany(StockOpname::class);
    }

    public function transfersOut(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, "from_warehouse_id");
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(WarehouseTransfer::class, "to_warehouse_id");
    }
}
