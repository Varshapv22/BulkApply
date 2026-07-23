<?php

namespace App\Notifications;

use App\Models\Plan;
use Illuminate\Notifications\Notification;

class PlanUpgraded extends Notification
{
    public function __construct(private Plan $plan)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => "Your plan has been upgraded to \"{$this->plan->name}\".",
            'plan_id' => $this->plan->id,
        ];
    }
}
