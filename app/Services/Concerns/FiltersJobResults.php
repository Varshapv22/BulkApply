<?php

namespace App\Services\Concerns;

use Illuminate\Support\Str;

/**
 * Post-fetch filtering shared by every aggregated job source (Adzuna, JSearch, …):
 * drop staffing/recruitment-agency ads, require the role to actually appear in
 * the ad, and truncate descriptions for display. Split out of JobSearchService
 * so a second aggregator doesn't have to duplicate (and risk drifting from) the
 * same heuristics.
 */
trait FiltersJobResults
{
    /**
     * Keep only jobs whose title OR description contains at least one
     * significant word from the searched role. A title match is the
     * strongest signal, but most direct-employer ads use a generic title
     * (e.g. "PHP Developer") and name the actual stack ("Laravel") only in
     * the body — restricting to the title alone was discarding those (real,
     * verified-relevant) ads and left only the handful that happened to
     * spell the role out in the title. Falls back to the unfiltered list if
     * nothing would survive (e.g. an unusual role phrase), so a search never
     * returns zero results because of this heuristic alone.
     */
    private function filterByTitleRelevance(array $jobs, string $role): array
    {
        $tokens = array_values(array_unique(array_filter(
            preg_split('/[\s\/,]+/', mb_strtolower(trim($role))),
            fn ($t) => mb_strlen($t) >= 3
        )));
        if (empty($tokens)) {
            return $jobs;
        }

        $filtered = array_values(array_filter($jobs, function ($job) use ($tokens) {
            $haystack = mb_strtolower(($job['job_title'] ?? '') . ' ' . ($job['description'] ?? ''));
            foreach ($tokens as $t) {
                if (str_contains($haystack, $t)) return true;
            }
            return false;
        }));

        return $filtered ?: $jobs;
    }

    /**
     * Drop ads posted by staffing/recruitment/consultancy agencies so only
     * direct employer vacancies remain. Unlike filterByTitleRelevance, this
     * has no "fall back to unfiltered" safety net — showing an agency ad
     * because the alternative is fewer results defeats the point of the filter.
     */
    private function filterOutAgencies(array $jobs): array
    {
        // Company-name signals: the agency's own name usually gives it away.
        $companyKeywords = [
            'staffing', 'consultanc', 'recruit', 'placement', 'manpower',
            'outsourc', 'talent acquisition', 'talent solutions', 'hr solutions',
            'hr services', 'workforce solutions', 'people solutions', 'headhunt',
            'hiring partner', 'jobs portal', 'career solutions',
        ];

        // Description-phrase signals: how an agency ad reads even when the
        // company name itself looks neutral (e.g. "Confidential").
        $descriptionKeywords = [
            'on behalf of our client', 'on behalf of one of our client',
            'one of our clients', 'one of our esteemed clients', 'multiple clients',
            'leading recruitment', 'staffing solutions', 'placement consultancy',
            'placement services', 'our client is looking', 'our client is hiring',
            'panel of clients', 'reputed client', 'client company', 'mnc client',
            'recruitment agency', 'recruitment firm', 'staffing agency',
        ];

        return array_values(array_filter($jobs, function ($job) use ($companyKeywords, $descriptionKeywords) {
            $company = mb_strtolower($job['company'] ?? '');
            foreach ($companyKeywords as $kw) {
                if (str_contains($company, $kw)) {
                    return false;
                }
            }

            $description = mb_strtolower($job['description'] ?? '');
            foreach ($descriptionKeywords as $kw) {
                if (str_contains($description, $kw)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /** Shorten descriptions to display length. Run only after all filtering is done. */
    private function truncateDescriptions(array $jobs): array
    {
        return array_map(function ($job) {
            $job['description'] = Str::limit($job['description'] ?? '', 200);
            return $job;
        }, $jobs);
    }
}
