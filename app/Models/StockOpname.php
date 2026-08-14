<?php

namespace App\Models;

use App\Concerns\Auditable;
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
        if ($this->status !== StockOpnameStatus::STATUS_DRAFT) {
            throw new RuntimeException('Only a Draft opname can be started.');
        }

        $this->update(['status' => StockOpnameStatus::STATUS_IN_PROGRESS]);
        $this->logAudit('started');
    }

    /**
     * Completed -> back to In Progress for a recount. Nothing to reverse —
     * approve() never ran, so stock was never touched.
     */
    public function reject(): void
    {
        if ($this->status !== StockOpnameStatus::STATUS_COMPLETED) {
            throw new RuntimeException(
                'Only a Completed opname can be rejected back for recount.',
            );
        }

        $this->update(['status' => StockOpnameStatus::STATUS_IN_PROGRESS]);
        $this->logAudit('rejected_for_recount');
    }
}
