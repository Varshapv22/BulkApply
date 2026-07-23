<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AccountStatusChanged extends Notification
{
    public function __construct(private bool $active)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => $this->active
                ? 'Your account has been reactivated.'
                : 'Your account has been suspended by an administrator.',
        ];
    }
}
