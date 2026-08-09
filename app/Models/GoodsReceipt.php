<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\GoodsReceiptStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoodsReceipt extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'supplier_id',
        'warehouse_id',
        'received_by',
        'receipt_number',
        'po_number',
        'date',
        'status',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
        'status' => GoodsReceiptStatus::class,
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    /**
     * Approve this receipt: stock is only ever changed here, never at draft/creation.
     * This is the core audit-trail invariant for the whole system — do not add
     * stock-mutating logic anywhere else in the Goods Receipt flow.
     */
    public function approve(): void
    {
        if ($this->status !== GoodsReceiptStatus::STATUS_DRAFT) {
            throw new RuntimeException('Only a Draft receipt can be approved.');
        }

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $item->product()->increment('stock', $item->qty);
            }

            $this->update(['status' => GoodsReceiptStatus::STATUS_RECEIVED]);
            $this->logAudit('approved');

            // AuditLog + notification dispatch (low_stock check, approval notice)
            // hook in here once the Supporting batch (AuditLog + Observer) is generated.
        });
    }

    public function cancel(): void
    {
        if ($this->status !== GoodsReceiptStatus::STATUS_DRAFT) {
            throw new RuntimeException(
                'Only a Draft receipt can be cancelled.',
            );
        }

        $this->update(['status' => GoodsReceiptStatus::STATUS_CANCELLED]);
        $this->logAudit('cancelled');
    }
}
