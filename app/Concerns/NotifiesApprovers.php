<?php

namespace App\Concerns;

use App\Models\User;
use App\Notifications\ApprovalNeededNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

trait NotifiesApprovers
{
    /**
     * Notify every Super Admin that a new document needs their approval —
     * but only if the creator isn't a Super Admin themselves (no point
     * notifying someone about their own document, and Super Admins can
     * approve their own drafts anyway per the Policies).
     */
    protected function notifyApprovers(string $documentLabel, string $documentNumber, string $link): void
    {
        /** @var User $creator */
        $creator = Auth::user();

        if ($creator->hasRole('Super Admin')) {
            return;
        }

        $approvers = User::query()->role('Super Admin')->get();

        if ($approvers->isEmpty()) {
            return;
        }

        Notification::send(
            $approvers,
            new ApprovalNeededNotification($documentLabel, $documentNumber, $link, $creator->name)
        );
    }
}
