<?php

namespace App\Notifications;

use App\Models\WarehouseTransfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TransferInTransitNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected WarehouseTransfer $transfer)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Store the notification data to notifications table
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Incoming Transfer',
            'message' => sprintf(
                'Transfer %s from %s is on its way — %d item line(s) to receive.',
                $this->transfer->transfer_number,
                $this->transfer->fromWarehouse->name,
                $this->transfer->items()->count(),
            ),
            'link' => "/warehouse-transfers/{$this->transfer->id}",
        ];
    }
}
