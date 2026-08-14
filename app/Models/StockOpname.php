<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Domain\StockOpname\States\ApprovedState;
use App\Domain\StockOpname\States\CompletedState;
use App\Domain\StockOpname\States\DraftState;
use App\Domain\StockOpname\States\InProgressState;
use App\Domain\StockOpname\States\StockOpnameState;
use App\Enums\StockOpnameStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

#[Fillable([
    'warehouse_id',
    'assigned_to',
    'approved_by',
    'opname_number',
    'title',
    'start_date',
    'completed_date',
    'status',
    'total_system_qty',
    'total_physical_qty',
    'total_variance_qty',
    'total_variance_value',
    'notes',
    'approved_at',
])]
class StockOpname extends Model
{
    use Auditable, HasFactory;

    protected $casts = [
        'start_date' => 'date',
        'completed_date' => 'date',
        'approved_at' => 'datetime',
        'total_variance_value' => 'decimal:2',
        'status' => StockOpnameStatus::class,
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }

    /**
     * Draft -> In Progress. No stock impact — this just opens the count for scanning.
     */
     public function start(): void
     {
         if (! $this->canStart()) {
             throw new RuntimeException(
                 'Stock opname cannot be started in its current state.',
             );
         }

         $this->update([
             'status' => StockOpnameStatus::STATUS_IN_PROGRESS,
         ]);

         $this->logAudit('started');
     }

    /**
     * Completed -> back to In Progress for a recount. Nothing to reverse —
     * approve() never ran, so stock was never touched.
     */
     public function reject(): void
     {
         if (! $this->canReject()) {
             throw new RuntimeException(
                 'Stock opname cannot be rejected in its current state.',
             );
         }

         $this->update([
             'status' => StockOpnameStatus::STATUS_IN_PROGRESS,
         ]);

         $this->logAudit('rejected_for_recount');
     }

    public function state(): StockOpnameState
    {
        return match ($this->status) {
            StockOpnameStatus::STATUS_DRAFT => new DraftState,
            StockOpnameStatus::STATUS_IN_PROGRESS => new InProgressState,
            StockOpnameStatus::STATUS_COMPLETED => new CompletedState,
            StockOpnameStatus::STATUS_APPROVED => new ApprovedState,
            default => throw new RuntimeException(
                "Unsupported stock opname status: {$this->status}",
            ),
        };
    }

    public function canStart(): bool
    {
        return $this->state()->canStart($this);
    }

    public function canComplete(): bool
    {
        return $this->state()->canComplete($this);
    }

    public function canApprove(): bool
    {
        return $this->state()->canApprove($this);
    }

    public function canReject(): bool
    {
        return $this->state()->canReject($this);
    }
}
