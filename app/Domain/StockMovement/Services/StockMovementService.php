<?php

namespace App\Domain\StockMovement\Services;

use App\Models\Product;

class StockMovementService
{
    /**
     * Adds stock. No guard needed — receiving stock (or an IN adjustment,
     * or a transfer arriving) can never fail on quantity.
     */
    public function increase(Product $product, int $qty): void
    {
        $product->increment('stock', $qty);
    }

    /**
     * Removes stock. This is the one method that carries real risk of
     * failure, so it's the one place that needs the row lock + guard —
     * previously duplicated near-identically in GoodsIssue::approve(),
     * StockAdjustment::approve() (OUT case), and
     * WarehouseTransfer::markInTransit().
     *
     * Re-fetches the product with a row lock rather than trusting whatever
     * $product instance the caller passed in — the caller may have loaded
     * it earlier in the request, and stock could have changed since then
     * from a concurrent transaction. The lock must happen right before the
     * check, not before the caller's own earlier read.
     */
    public function decrease(Product $product, int $qty): void
    {
        $locked = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

        if ($locked->stock < $qty) {
            throw new \RuntimeException(
                "Insufficient stock for {$locked->sku}: have {$locked->stock}, need {$qty}."
            );
        }

        $locked->decrement('stock', $qty);

        // Keep the caller's in-memory instance consistent with what we just
        // persisted, in case they read $product->stock again afterward.
        $product->setAttribute('stock', $locked->stock);
    }
}
