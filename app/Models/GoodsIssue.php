<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\GoodsIssueStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GoodsIssue extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        "customer_id",
        "warehouse_id",
        "issued_by",
        "issue_number",
        "so_number",
        "date",
        "status",
        "total_amount",
        "notes",
    ];

    protected $casts = [
        "date" => "date",
        "total_amount" => "decimal:2",
        "status" => GoodsIssueStatus::class,
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
        return $this->belongsTo(User::class, "issued_by");
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsIssueItem::class);
    }

    /**
     * Approve this issue: stock only changes here, never at draft/creation.
     * Guards against any line driving a product's stock negative — the whole
     * batch is rejected atomically if that happens (no partial deduction).
     */
    public function approve(): void
    {
        if ($this->status !== GoodsIssueStatus::STATUS_DRAFT) {
            throw new RuntimeException("Only a Draft issue can be approved.");
        }

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $product = $item->product()->lockForUpdate()->first();

                if ($product->stock < $item->qty) {
                    throw new RuntimeException(
                        "Insufficient stock for {$product->sku}: have {$product->stock}, need {$item->qty}.",
                    );
                }

                $product->decrement("stock", $item->qty);
            }

            $this->update(["status" => GoodsIssueStatus::STATUS_ISSUED]);
            $this->logAudit("approved");

            // AuditLog + low_stock notification dispatch hooks in here
            // once the Supporting batch is generated.
        });
    }

    public function cancel(): void
    {
        if ($this->status !== GoodsIssueStatus::STATUS_DRAFT) {
            throw new RuntimeException("Only a Draft issue can be cancelled.");
        }

        $this->update(["status" => GoodsIssueStatus::STATUS_CANCELLED]);
        $this->logAudit("cancelled");
    }
}
