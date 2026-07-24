<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SubscriptionCancelled extends Notification
{
    public function __construct(private ?string $planName)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $plan = $this->planName ? " (\"{$this->planName}\")" : '';

        return [
            'message' => "Your subscription{$plan} was cancelled.",
        ];
    }
}
