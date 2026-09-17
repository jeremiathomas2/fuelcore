<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use App\Notifications\AlertRaised;
use Illuminate\Notifications\DatabaseNotification as DbNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AlertService
{
    /**
     * Roles that receive operational alerts.
     *
     * @var array<int, string>
     */
    public const RECIPIENT_ROLES = ['super_admin', 'head_office_admin', 'station_manager'];

    /**
     * Raise an alert, de-duplicating identical unresolved entries per entity.
     */
    public static function raise(
        string $severity,
        string $title,
        string $message,
        ?int $stationId = null,
        ?string $entityType = null,
        ?int $entityId = null,
    ): Alert {
        $severity = in_array($severity, Alert::SEVERITIES, true) ? $severity : 'warning';

        $duplicate = Alert::query()
            ->whereNull('resolved_at')
            ->where('title', $title)
            ->when($stationId, fn ($q) => $q->where('station_id', $stationId), fn ($q) => $q->whereNull('station_id'))
            ->when(
                $entityType,
                fn ($q) => $q->where('entity_type', $entityType)->where('entity_id', $entityId),
                fn ($q) => $q->whereNull('entity_type'),
            )
            ->first();

        if ($duplicate) {
            return $duplicate;
        }

        $alert = Alert::create([
            'station_id' => $stationId,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);

        static::notify($alert);

        return $alert;
    }

    public static function resolve(Alert $alert, ?User $user = null): Alert
    {
        $alert->markResolved($user);

        return $alert;
    }

    /**
     * Fan the alert out to every eligible user (database notifications).
     *
     * Delivery is intentionally synchronous so notifications are never lost when
     * no queue worker is running. Failures are logged but never bubble up — an
     * alert must not roll back the business transaction that triggered it.
     */
    protected static function notify(Alert $alert): void
    {
        try {
            $recipients = static::recipients($alert);

            if ($recipients->isEmpty()) {
                return;
            }

            $alreadyNotified = DbNotification::query()
                ->where('notifiable_type', User::class)
                ->whereIn('notifiable_id', $recipients->pluck('id'))
                ->get()
                ->filter(fn ($n) => (int) data_get($n->data, 'alert_id') === (int) $alert->id)
                ->pluck('notifiable_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $recipients = $recipients->reject(fn (User $user) => in_array((int) $user->id, $alreadyNotified, true));

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new AlertRaised($alert));
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch alert notifications', [
                'alert_id' => $alert->id,
                'title' => $alert->title,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Every active recipient that should see this alert, with no artificial cap.
     *
     * @return Collection<int, User>
     */
    protected static function recipients(Alert $alert): Collection
    {
        return User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', self::RECIPIENT_ROLES))
            ->when($alert->station_id, function ($q) use ($alert) {
                $q->where(function ($sub) use ($alert) {
                    $sub->whereHas('stations', fn ($s) => $s->where('station_id', $alert->station_id))
                        ->orWhereNull('station_id');
                });
            })
            ->distinct()
            ->get();
    }
}
