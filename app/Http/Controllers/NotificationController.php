<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($this->perPage())
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Live feed used by the header bell to poll for new notifications.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = $user->unreadNotifications()
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (DatabaseNotification $notification) {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'title' => $data['title'] ?? 'Notification',
                    'message' => $data['message'] ?? '',
                    'severity' => $data['severity'] ?? 'info',
                    'time' => $notification->created_at?->diffForHumans() ?? '',
                    'url' => isset($data['alert_id']) ? route('alerts.index') : route('notifications.index'),
                ];
            })
            ->values();

        return response()->json([
            'unread' => $user->unreadNotifications()->count(),
            'items' => $items,
        ]);
    }

    public function markRead(Request $request, DatabaseNotification $notification): JsonResponse|RedirectResponse
    {
        abort_unless(
            $notification->notifiable_type === $request->user()->getMorphClass()
                && (int) $notification->notifiable_id === (int) $request->user()->id,
            403,
        );

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        if ($request->expectsJson()) {
            return response()->json(['unread' => $request->user()->unreadNotifications()->count()]);
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['unread' => 0]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }
}
