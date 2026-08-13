<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected Product $product)
    {
        //
    }

    /**
     * Store the notification data to notifications table
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $isOutOfStock = $this->product->stock <= 0;

        return [
            'title' => $isOutOfStock ? 'Out of Stock' : 'Low Stock',
            'message' => sprintf(
                '%s at %s is %s (%d %s remaining, min %d).',
                $this->product->name,
                $this->product->warehouse->name,
                $isOutOfStock ? 'out of stock' : 'running low',
                $this->product->stock,
                $this->product->unit->symbol,
                $this->product->min_stock,
            ),
            'link' => "/products/{$this->product->id}",
            'product_id' => $this->product->id,
            'warehouse_id' => $this->product->warehouse_id,
        ];
    }
}
