<?php

namespace App\Traits;

use App\Models\NotificationPreference;

trait HasNotificationPreferences
{
     public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function getNotificationChannels(string $type): array
    {
        $preference = $this->notificationPreferences()
            ->where('notification_type', $type)
            ->first();

        if ($preference && $preference->is_enabled && $preference->channels) {
            return $preference->channels;
        }

        // Retourner les canaux par défaut depuis la config
        return config("notifications.preferences.{$type}.default", ['database']);
    }

    public function updateNotificationPreference(string $type, array $channels, bool $enabled = true): void
    {
        $this->notificationPreferences()->updateOrCreate(
            ['notification_type' => $type],
            ['channels' => $channels, 'is_enabled' => $enabled]
        );
    }

    public function initializeNotificationPreferences(): void
    {
        $defaults = config('notifications.preferences', []);

        foreach ($defaults as $type => $config) {
            $this->notificationPreferences()->firstOrCreate(
                ['notification_type' => $type],
                [
                    'channels' => $config['default'] ?? ['database'],
                    'is_enabled' => true
                ]
            );
        }
    }
}
