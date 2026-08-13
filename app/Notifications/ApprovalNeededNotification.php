<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ApprovalNeededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected string $documentLabel,
        protected string $documentNumber,
        protected string $link,
        protected string $createdByName,
    ) {}

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
            'title' => 'Approval Needed',
            'message' => sprintf(
                '%s %s from %s is waiting for your approval.',
                $this->documentLabel,
                $this->documentNumber,
                $this->createdByName,
            ),
            'link' => $this->link,
        ];
    }
}
