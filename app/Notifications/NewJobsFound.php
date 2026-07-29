<?php

namespace App\Notifications;

use App\Models\SavedSearch;
use Illuminate\Notifications\Notification;

class NewJobsFound extends Notification
{
    public function __construct(private SavedSearch $savedSearch, private int $count)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $what = $this->savedSearch->role . ($this->savedSearch->location ? " in {$this->savedSearch->location}" : '');

        return [
            'message' => "{$this->count} new job(s) found for \"{$what}\".",
            'saved_search_id' => $this->savedSearch->id,
            'url' => route('search.index', $this->savedSearch->toSearchQuery()),
        ];
    }
}
