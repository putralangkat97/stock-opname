<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\GoodsReceiptStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

#[Fillable([
    'supplier_id',
    'warehouse_id',
    'received_by',
    'receipt_number',
    'po_number',
    'date',
    'status',
    'total_amount',
    'notes',
])]
class GoodsReceipt extends Model
{
    use Auditable, HasFactory;

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
