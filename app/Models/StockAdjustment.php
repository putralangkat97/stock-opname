<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\StockAdjustmentReason;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockAdjustmentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockAdjustment extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'warehouse_id',
        'adjusted_by',
        'adjustment_number',
        'type',
        'reason',
        'date',
        'status',
        'notes',
    ];

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

        DB::transaction(function () {
            foreach ($this->items as $item) {
                if ($this->type === StockAdjustmentType::TYPE_IN) {
                    $item->product()->increment('stock', $item->qty);

                    continue;
                }

                $product = $item->product()->lockForUpdate()->first();

                if ($product->stock < $item->qty) {
                    throw new RuntimeException(
                        "Insufficient stock for {$product->sku}: have {$product->stock}, need {$item->qty}.",
                    );
                }

                $product->decrement('stock', $item->qty);
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
