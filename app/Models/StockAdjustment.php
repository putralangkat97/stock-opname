<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Domain\StockMovement\Services\StockMovementService;
use App\Enums\StockAdjustmentReason;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockAdjustmentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
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

    /**
     * Approve this adjustment: stock only changes here, never at draft/creation.
     * IN adds to stock, OUT removes it (with the same insufficient-stock guard
     * used by Goods Issue, so an OUT adjustment can never go negative).
     */
    public function approve(): void
    {
        if ($this->status !== StockAdjustmentStatus::STATUS_PENDING) {
            throw new RuntimeException(
                'Only a Pending adjustment can be approved.',
            );
        }

        $stockMovement = app(StockMovementService::class);

        DB::transaction(function () use ($stockMovement) {
            foreach ($this->items as $item) {
                if ($this->type === StockAdjustmentType::TYPE_IN) {
                    $stockMovement->increase($item->product, $item->qty);

                    continue;
                }

                $stockMovement->decrease($item->product, $item->qty);
            }

            $this->update(['status' => StockAdjustmentStatus::STATUS_APPROVED]);
            $this->logAudit('approved');

            // AuditLog + notification dispatch hooks in here once the
            // Supporting batch is generated.
        });
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
