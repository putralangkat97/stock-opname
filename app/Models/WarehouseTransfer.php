<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\WarehouseTransferStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

#[Fillable([
    'from_warehouse_id',
    'to_warehouse_id',
    'transferred_by',
    'received_by',
    'transfer_number',
    'date',
    'status',
    'notes',
])]
class WarehouseTransfer extends Model
{
    use Auditable, HasFactory;

    protected $casts = [
        'date' => 'date',
        'status' => WarehouseTransferStatus::class,
    ];

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseTransferItem::class);
    }

    protected static function booted(): void
    {
        static::saving(function (WarehouseTransfer $transfer) {
            if (
                $transfer->from_warehouse_id &&
                $transfer->from_warehouse_id === $transfer->to_warehouse_id
            ) {
                throw new RuntimeException(
                    'A transfer cannot have the same source and destination warehouse.',
                );
            }
        });
    }

    /**
     * Step 1: stock leaves the source warehouse. This is the ONLY place stock
     * is deducted for a transfer — never at creation.
     */
    public function markInTransit(): void
    {
        if ($this->status !== WarehouseTransferStatus::STATUS_PENDING) {
            throw new RuntimeException(
                'Only a Pending transfer can be marked In Transit.',
            );
        }

        DB::transaction(function () {
            foreach ($this->items as $item) {
                $product = $item->product()->lockForUpdate()->first();

                if ($product->stock < $item->qty) {
                    throw new RuntimeException(
                        "Insufficient stock for {$product->sku} at source warehouse: have {$product->stock}, need {$item->qty}.",
                    );
                }

                $product->decrement('stock', $item->qty);
            }

            $this->update([
                'status' => WarehouseTransferStatus::STATUS_IN_TRANSIT,
            ]);
            $this->logAudit('marked_in_transit');
        });
    }

    /**
     * Step 2: stock arrives at the destination warehouse. Finds the destination
     * warehouse's row for this SKU (creating it — cloned from the source product's
     * catalog attributes — if this is the first time that SKU has stock there).
     */
    public function complete(int $receivedByUserId): void
    {
        if ($this->status !== WarehouseTransferStatus::STATUS_IN_TRANSIT) {
            throw new RuntimeException(
                'Only an In Transit transfer can be completed.',
            );
        }

        DB::transaction(function () use ($receivedByUserId) {
            foreach ($this->items as $item) {
                $sourceProduct = $item->product;

                $destinationProduct = Product::query()->firstOrCreate(
                    [
                        'sku' => $sourceProduct->sku,
                        'warehouse_id' => $this->to_warehouse_id,
                    ],
                    [
                        'category_id' => $sourceProduct->category_id,
                        'brand_id' => $sourceProduct->brand_id,
                        'unit_id' => $sourceProduct->unit_id,
                        'bin_location_id' => null, // must be assigned manually at destination
                        'name' => $sourceProduct->name,
                        'stock' => 0,
                        'min_stock' => $sourceProduct->min_stock,
                        'max_stock' => $sourceProduct->max_stock,
                        'cost_price' => $sourceProduct->cost_price,
                        'selling_price' => $sourceProduct->selling_price,
                        'is_fast_moving' => $sourceProduct->is_fast_moving,
                    ],
                );

                $destinationProduct->increment('stock', $item->qty);
            }

            $this->update([
                'status' => WarehouseTransferStatus::STATUS_COMPLETED,
                'received_by' => $receivedByUserId,
            ]);
            $this->logAudit('completed');
        });
    }

    /**
     * Reject a transfer. If stock had already left the source (status was
     * In Transit), it's restored — a rejection must never cause stock to
     * vanish. If still Pending, nothing has moved yet, so nothing to reverse.
     */
    public function reject(): void
    {
        if (
            ! in_array(
                $this->status,
                [
                    WarehouseTransferStatus::STATUS_PENDING,
                    WarehouseTransferStatus::STATUS_IN_TRANSIT,
                ],
                true,
            )
        ) {
            throw new RuntimeException(
                'Only a Pending or In Transit transfer can be rejected.',
            );
        }

        DB::transaction(function () {
            if ($this->status === WarehouseTransferStatus::STATUS_IN_TRANSIT) {
                foreach ($this->items as $item) {
                    $item->product()->increment('stock', $item->qty);
                }
            }

            $this->update([
                'status' => WarehouseTransferStatus::STATUS_REJECTED,
            ]);
            $this->logAudit('rejected');
        });
    }
}
