<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * No Policy needed here — a notification's implicit "owner" is whoever
     * it was sent to, and Laravel's Notifiable relation is already scoped to
     * Auth::user(), so there's no way to mark someone else's notification
     * read through this action even without an explicit authorization check.
     */
    public function markAsRead(string $notificationId): RedirectResponse
    {
        $notification = Auth::user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        return back();
    }

    public function markAllAsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
