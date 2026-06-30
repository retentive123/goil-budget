<?php

namespace App\Http\Controllers;

use App\Models\BudgetNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = BudgetNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        // Mark all as read when viewing the list
        BudgetNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(BudgetNotification $notification)
    {
        $this->authorizeNotification($notification);
        $notification->markAsRead();

        return back();
    }

    public function markAllRead()
    {
        BudgetNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(BudgetNotification $notification)
    {
        $this->authorizeNotification($notification);
        $notification->delete();

        return back()->with('success', 'Notification deleted.');
    }

    private function authorizeNotification(BudgetNotification $notification): void
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
