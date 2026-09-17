<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification as DbNotification;

class AlertService
{
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
            ->when($entityType, fn ($q) => $q->where('entity_type', $entityType)->where('entity_id', $entityId))
            ->where('title', $title)
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
     * Fan the alert out to the users who need to see it (database notifications).
     */
    protected static function notify(Alert $alert): void
    {
        $users = User::query()
            ->where('status', 'active')
            ->whereHas('roles', function ($q) {
                $q->whereIn('slug', ['super_admin', 'head_office_admin', 'station_manager']);
            })
            ->when($alert->station_id, function ($q) use ($alert) {
                $q->where(function ($sub) use ($alert) {
                    $sub->whereHas('stations', fn ($s) => $s->where('station_id', $alert->station_id))
                        ->orWhereNull('station_id');
                });
            })
            ->limit(50)->get();

        foreach ($users as $user) {
            DbNotification::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'alert',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode([
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'severity' => $alert->severity,
                    'alert_id' => $alert->id,
                ]),
                'read_at' => null,
            ]);
        }
    }
}