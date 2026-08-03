<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(["name", "email", "password"])]
#[Hidden(["password", "remember_token"])]
class User extends Authenticatable
{
    /**
     * @use HasFactory<UserFactory>
     * @use Notifiable
     * @use HasRoles
     */
    use HasFactory, Notifiable, HasRoles;

    protected $casts = [
        "email_verified_at" => "datetime",
        "password" => "hashed",
    ];

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class);
    }

    // Convenience helper for Policies — use this instead of querying the pivot directly.
    public function hasAccessToWarehouse(Warehouse $warehouse): bool
    {
        return $this->hasRole("Super Admin") ||
            $this->warehouses()->whereKey($warehouse->id)->exists();
    }

    // --- The relationships below will resolve once we generate the transaction models ---

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class, "received_by");
    }

    public function goodsIssues(): HasMany
    {
        return $this->hasMany(GoodsIssue::class, "issued_by");
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, "adjusted_by");
    }

    public function assignedStockOpnames(): HasMany
    {
        return $this->hasMany(StockOpname::class, "assigned_to");
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
