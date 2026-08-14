<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /** @return BelongsToMany */
    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_user');
    }

    /** Convenience helper for Policies — use this instead of querying the pivot directly.
     *
     * @param Warehouse $warehouse
     *
     * @return bool
     */
    public function hasAccessToWarehouse(Warehouse $warehouse): bool
    {
        return $this->hasRole('Super Admin') ||
            $this->warehouses()->whereKey($warehouse->id)->exists();
    }

    // --- The relationships below will resolve once we generate the transaction models ---

    /** @return HasMany */
    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, 'received_by');
    }

    /** @return HasMany */
    public function goodsIssues(): HasMany
    {
        return $this->hasMany(GoodsIssue::class, 'issued_by');
    }

    /** @return HasMany */
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'adjusted_by');
    }

    /** @return HasMany */
    public function assignedStockOpnames(): HasMany
    {
        return $this->hasMany(StockOpname::class, 'assigned_to');
    }

    /** @return HasMany */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
