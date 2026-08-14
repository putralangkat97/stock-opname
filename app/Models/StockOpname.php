<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\StockAdjustmentStatus;
use App\Enums\StockAdjustmentType;
use App\Enums\StockOpnameStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * Completed -> Approved. This is where stock actually changes for an
     * opname — never before. Every line with a nonzero variance gets a real
     * Stock Adjustment created AND approved (reusing StockAdjustment::approve()
     * as the single source of truth for stock mutation, rather than duplicating
     * that logic here).
     */
    public function approve(int $approverId): void
    {
        if ($this->status !== StockOpnameStatus::STATUS_COMPLETED) {
            throw new RuntimeException(
                'Only a Completed opname can be approved.',
            );
        }

        DB::transaction(function () use ($approverId) {
            $varianceItems = $this->items()
                ->whereColumn('physical_qty', '!=', 'system_qty')
                ->get();

            if ($varianceItems->isNotEmpty()) {
                // StockAdjustment.type is a single IN/OUT per document, but an opname can
                // contain both surplus and shortage lines in the same count. Split into up
                // to two adjustments (one IN, one OUT) rather than force a single direction.
                $surplus = $varianceItems->filter(
                    fn (StockOpnameItem $i) => $i->physical_qty > $i->system_qty,
                );
                $shortage = $varianceItems->filter(
                    fn (StockOpnameItem $i) => $i->physical_qty < $i->system_qty,
                );

                foreach (
                    [
                        [StockAdjustmentType::TYPE_IN, $surplus],
                        [StockAdjustmentType::TYPE_OUT, $shortage],
                    ] as [$type, $lines]
                ) {
                    if ($lines->isEmpty()) {
                        continue;
                    }

                    $adj = StockAdjustment::query()->create([
                        'warehouse_id' => $this->warehouse_id,
                        'adjusted_by' => $approverId,
                        'adjustment_number' => 'ADJ-OPN-'.
                            $this->opname_number.
                            '-'.
                            $type.
                            '-'.
                            Str::upper(Str::random(4)),
                        'type' => $type,
                        'reason' => 'Correction',
                        'date' => now()->toDateString(),
                        'status' => StockAdjustmentStatus::STATUS_PENDING,
                        'notes' => "Auto-generated from Stock Opname {$this->opname_number}",
                    ]);

                    foreach ($lines as $line) {
                        $adj->items()->create([
                            'product_id' => $line->product_id,
                            'product_sku_snapshot' => $line->product_sku_snapshot,
                            'product_name_snapshot' => $line->product_name_snapshot,
                            'qty' => abs(
                                $line->physical_qty - $line->system_qty,
                            ),
                        ]);
                    }

                    $adj->refresh();
                    $adj->approve(); // applies the actual stock mutation
                }
            }

            $this->update([
                'status' => StockOpnameStatus::STATUS_APPROVED,
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);
            $this->logAudit('approved');
        });
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
