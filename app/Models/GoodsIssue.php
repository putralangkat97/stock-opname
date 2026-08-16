<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\GoodsIssueStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

#[Fillable([
    'customer_id',
    'warehouse_id',
    'issued_by',
    'issue_number',
    'so_number',
    'date',
    'status',
    'total_amount',
    'notes',
])]
class GoodsIssue extends Model
{
    use Auditable, HasFactory;

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
        'status' => GoodsIssueStatus::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsIssueItem::class);
    }

    public function cancel(): void
    {
        if ($this->status !== GoodsIssueStatus::STATUS_DRAFT) {
            throw new RuntimeException('Only a Draft issue can be cancelled.');
        }

        $this->update(['status' => GoodsIssueStatus::STATUS_CANCELLED]);
        $this->logAudit('cancelled');
    }
}
