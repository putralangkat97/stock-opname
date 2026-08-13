<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Notification;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        if (! $product->wasChanged('stock')) {
            return;
        }

        $threshold = $product->min_stock;
        $previousStock = $product->getOriginal('stock');
        $currentStock = $product->stock;

        // Only notify on the transition INTO low/out-of-stock, not every
        // subsequent change while it stays low — otherwise a warehouse
        // doing several small issues against an already-low product would
        // get spammed with a notification per transaction.
        $wasAboveThreshold = $previousStock > $threshold;
        $isAtOrBelowThreshold = $currentStock <= $threshold;

        if (! ($wasAboveThreshold && $isAtOrBelowThreshold)) {
            return;
        }

        $recipients = User::query()
            ->role('Super Admin')
            ->get()
            ->merge(
                User::query()
                    ->whereHas('warehouses', fn ($q) => $q->whereKey($product->warehouse_id))
                    ->role('Warehouse Admin')
                    ->get()
            )
            ->unique('id');

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new LowStockNotification($product));
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
