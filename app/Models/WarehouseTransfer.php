<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\WarehouseTransferStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

#[Fillable([
    'from_warehouse_id',
    'to_warehouse_id',
    'transferred_by',
    'received_by',
    'transfer_number',
    'date',
    'status',
    'notes',
])]
class WarehouseTransfer extends Model
{
    use Auditable, HasFactory;

    protected $casts = [
        'date' => 'date',
        'status' => WarehouseTransferStatus::class,
    ];

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }

    protected static function booted(): void
    {
        static::saving(function (WarehouseTransfer $transfer) {
            if (
                $transfer->from_warehouse_id &&
                $transfer->from_warehouse_id === $transfer->to_warehouse_id
            ) {
                throw new RuntimeException(
                    'A transfer cannot have the same source and destination warehouse.',
                );
            }
        });
    }
}
