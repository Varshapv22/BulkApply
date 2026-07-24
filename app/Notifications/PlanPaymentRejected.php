<?php

namespace App\Notifications;

use App\Models\Plan;
use Illuminate\Notifications\Notification;

class PlanPaymentRejected extends Notification
{
    public function __construct(private Plan $plan, private ?string $reason)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $reason = $this->reason ? " Reason: {$this->reason}" : '';

        return [
            'message' => "Your payment for the \"{$this->plan->name}\" plan couldn't be verified.{$reason}",
            'plan_id' => $this->plan->id,
        ];
    }
}
