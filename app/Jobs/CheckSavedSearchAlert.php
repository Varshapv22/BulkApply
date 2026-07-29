<?php

namespace App\Jobs;

use App\Models\SavedSearch;
use App\Models\SavedSearchSeenListing;
use App\Notifications\NewJobsFound;
use App\Services\JobQueryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckSavedSearchAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $savedSearchId)
    {
    }

    public function handle(JobQueryService $service): void
    {
        $saved = SavedSearch::find($this->savedSearchId);

        if (!$saved || !$saved->is_active) {
            return;
        }

        $result = $service->run($saved->role, $saved->location ?? '', $saved->site ?? '', [
            'find_contacts' => false,
        ], 30);

        $jobs = $result['jobs'] ?? [];

        $fingerprints = collect($jobs)->map(fn ($job) => $this->fingerprint($job))->unique();
        $known = $saved->seenListings()->pluck('fingerprint')->flip();
        $newFingerprints = $fingerprints->reject(fn ($fp) => $known->has($fp));

        foreach ($fingerprints as $fingerprint) {
            SavedSearchSeenListing::firstOrCreate(
                ['saved_search_id' => $saved->id, 'fingerprint' => $fingerprint],
                ['first_seen_at' => now()]
            );
        }

        // Only alert once the search has a baseline from a prior run — the
        // very first check would otherwise "discover" every existing result
        // as new and spam the user on save.
        if ($saved->is_baselined && $newFingerprints->isNotEmpty()) {
            $saved->user->notify(new NewJobsFound($saved, $newFingerprints->count()));
        }

        $saved->update(['last_checked_at' => now(), 'is_baselined' => true]);
    }

    private function fingerprint(array $job): string
    {
        $norm = fn (string $s) => preg_replace('/\s+/', ' ', trim(mb_strtolower(strip_tags($s))));

        return hash('sha256', implode('|', [
            $norm($job['company'] ?? ''),
            $norm($job['job_title'] ?? ''),
            $norm($job['source'] ?? ''),
        ]));
    }
}
