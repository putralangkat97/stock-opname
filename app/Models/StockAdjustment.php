<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\StockAdjustmentReason;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockAdjustmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

#[Fillable([
    'warehouse_id',
    'adjusted_by',
    'adjustment_number',
    'type',
    'reason',
    'date',
    'status',
    'notes',
])]
class StockAdjustment extends Model
{
    use Auditable, HasFactory;

    protected $casts = [
        'date' => 'date',
        'type' => StockAdjustmentType::class,
        'status' => StockAdjustmentStatus::class,
        'reason' => StockAdjustmentReason::class,
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function adjustedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockAdjustmentItem::class);
    }

    public function reject(): void
    {
        if ($this->status !== StockAdjustmentStatus::STATUS_PENDING) {
            throw new RuntimeException(
                'Only a Pending adjustment can be rejected.',
            );
        }

        // No stock reversal needed — approval (and therefore any stock
        // mutation) never happened for a Pending adjustment.
        $this->update(['status' => StockAdjustmentStatus::STATUS_REJECTED]);
        $this->logAudit('rejected');
    }
}
