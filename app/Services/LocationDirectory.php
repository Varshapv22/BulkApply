<?php

namespace App\Services;

/**
 * Curated location → country lookup. Two jobs:
 *  1. Powers the Location field's autocomplete suggestions on Find Jobs.
 *  2. Lets JobSearchService resolve free-text location input to the right
 *     country, and route to Adzuna (for countries it indexes) or JSearch
 *     (everywhere else — UAE, the rest of the Gulf, and most of Asia beyond
 *     India/Singapore) instead of always querying whichever country Adzuna
 *     is configured for (previously: searching "Dubai" still queried
 *     Adzuna's India index and returned unrelated India results, because
 *     Adzuna's "where" param only filters *within* one country).
 *
 * Not exhaustive — a curated list of common cities/regions/countries.
 * Unrecognised input resolves to null, and callers fall back to the
 * previous behaviour (the configured default country).
 */
class LocationDirectory
{
    /** Country codes Adzuna's Job Search API actually covers (developer.adzuna.com). */
    public const ADZUNA_COUNTRIES = [
        'in' => 'India', 'gb' => 'United Kingdom', 'us' => 'United States', 'au' => 'Australia',
        'ca' => 'Canada', 'de' => 'Germany', 'fr' => 'France', 'nz' => 'New Zealand',
        'za' => 'South Africa', 'pl' => 'Poland', 'nl' => 'Netherlands', 'it' => 'Italy',
        'es' => 'Spain', 'at' => 'Austria', 'be' => 'Belgium', 'br' => 'Brazil',
        'mx' => 'Mexico', 'sg' => 'Singapore', 'ch' => 'Switzerland',
    ];

    /**
     * label: shown in the autocomplete dropdown.
     * country: ISO code, or null for a location with no single country (Remote).
     * aliases: lowercase strings matched against the typed location; the
     *          longest matching alias wins when several could apply.
     */
    private const PLACES = [
        ['label' => 'Remote', 'country' => null, 'aliases' => ['remote']],

        // India — the most common source today, kept first.
        ['label' => 'Kerala, India', 'country' => 'in', 'aliases' => ['kerala']],
        ['label' => 'Kochi, Kerala', 'country' => 'in', 'aliases' => ['kochi', 'cochin']],
        ['label' => 'Kozhikode, Kerala', 'country' => 'in', 'aliases' => ['kozhikode', 'calicut']],
        ['label' => 'Thiruvananthapuram, Kerala', 'country' => 'in', 'aliases' => ['thiruvananthapuram', 'trivandrum']],
        ['label' => 'Kollam, Kerala', 'country' => 'in', 'aliases' => ['kollam']],
        ['label' => 'Bangalore, India', 'country' => 'in', 'aliases' => ['bangalore', 'bengaluru']],
        ['label' => 'Mumbai, India', 'country' => 'in', 'aliases' => ['mumbai']],
        ['label' => 'Delhi, India', 'country' => 'in', 'aliases' => ['delhi', 'new delhi']],
        ['label' => 'Hyderabad, India', 'country' => 'in', 'aliases' => ['hyderabad']],
        ['label' => 'Chennai, India', 'country' => 'in', 'aliases' => ['chennai']],
        ['label' => 'Pune, India', 'country' => 'in', 'aliases' => ['pune']],
        ['label' => 'India', 'country' => 'in', 'aliases' => ['india']],

        // Gulf / UAE — real, common searches on this app, but NOT covered by
        // Adzuna; routed to JSearch instead (see JobSearchService::search()).
        ['label' => 'Dubai, UAE', 'country' => 'ae', 'aliases' => ['dubai']],
        ['label' => 'Abu Dhabi, UAE', 'country' => 'ae', 'aliases' => ['abu dhabi']],
        ['label' => 'Sharjah, UAE', 'country' => 'ae', 'aliases' => ['sharjah']],
        ['label' => 'United Arab Emirates', 'country' => 'ae', 'aliases' => ['uae', 'united arab emirates']],
        ['label' => 'Doha, Qatar', 'country' => 'qa', 'aliases' => ['doha', 'qatar']],
        ['label' => 'Riyadh, Saudi Arabia', 'country' => 'sa', 'aliases' => ['riyadh', 'saudi arabia']],
        ['label' => 'Jeddah, Saudi Arabia', 'country' => 'sa', 'aliases' => ['jeddah']],
        ['label' => 'Kuwait City, Kuwait', 'country' => 'kw', 'aliases' => ['kuwait']],
        ['label' => 'Manama, Bahrain', 'country' => 'bh', 'aliases' => ['bahrain', 'manama']],
        ['label' => 'Muscat, Oman', 'country' => 'om', 'aliases' => ['muscat', 'oman']],

        // Rest of Asia — also not covered by Adzuna, also routed to JSearch.
        ['label' => 'Kuala Lumpur, Malaysia', 'country' => 'my', 'aliases' => ['kuala lumpur', 'malaysia']],
        ['label' => 'Manila, Philippines', 'country' => 'ph', 'aliases' => ['manila', 'philippines']],
        ['label' => 'Jakarta, Indonesia', 'country' => 'id', 'aliases' => ['jakarta', 'indonesia']],
        ['label' => 'Bangkok, Thailand', 'country' => 'th', 'aliases' => ['bangkok', 'thailand']],
        ['label' => 'Ho Chi Minh City, Vietnam', 'country' => 'vn', 'aliases' => ['ho chi minh', 'hanoi', 'vietnam']],
        ['label' => 'Tokyo, Japan', 'country' => 'jp', 'aliases' => ['tokyo', 'japan']],
        ['label' => 'Seoul, South Korea', 'country' => 'kr', 'aliases' => ['seoul', 'south korea']],
        ['label' => 'Shanghai, China', 'country' => 'cn', 'aliases' => ['shanghai', 'beijing', 'china']],
        ['label' => 'Hong Kong', 'country' => 'hk', 'aliases' => ['hong kong']],
        ['label' => 'Colombo, Sri Lanka', 'country' => 'lk', 'aliases' => ['colombo', 'sri lanka']],
        ['label' => 'Dhaka, Bangladesh', 'country' => 'bd', 'aliases' => ['dhaka', 'bangladesh']],
        ['label' => 'Karachi, Pakistan', 'country' => 'pk', 'aliases' => ['karachi', 'lahore', 'islamabad', 'pakistan']],
        ['label' => 'Kathmandu, Nepal', 'country' => 'np', 'aliases' => ['kathmandu', 'nepal']],

        // Other Adzuna-covered countries.
        ['label' => 'London, UK', 'country' => 'gb', 'aliases' => ['london']],
        ['label' => 'United Kingdom', 'country' => 'gb', 'aliases' => ['uk', 'united kingdom']],
        ['label' => 'New York, USA', 'country' => 'us', 'aliases' => ['new york']],
        ['label' => 'San Francisco, USA', 'country' => 'us', 'aliases' => ['san francisco']],
        ['label' => 'United States', 'country' => 'us', 'aliases' => ['usa', 'united states']],
        ['label' => 'Toronto, Canada', 'country' => 'ca', 'aliases' => ['toronto']],
        ['label' => 'Canada', 'country' => 'ca', 'aliases' => ['canada']],
        ['label' => 'Sydney, Australia', 'country' => 'au', 'aliases' => ['sydney']],
        ['label' => 'Australia', 'country' => 'au', 'aliases' => ['australia']],
        ['label' => 'Singapore', 'country' => 'sg', 'aliases' => ['singapore']],
        ['label' => 'Berlin, Germany', 'country' => 'de', 'aliases' => ['berlin']],
        ['label' => 'Germany', 'country' => 'de', 'aliases' => ['germany']],
    ];

    /**
     * Suggestion list for the frontend, each flagged with whether our web-wide
     * search actually covers it right now. $internationalAvailable reflects
     * whether JSearch is configured — pass it from the caller (which knows
     * the live FeatureFlag/API-key state) rather than checking it here, so
     * this class stays a pure lookup with no side effects.
     */
    public static function suggestions(bool $internationalAvailable = false): array
    {
        return array_map(fn ($p) => [
            'label'     => $p['label'],
            'country'   => $p['country'],
            'supported' => $p['country'] === null || isset(self::ADZUNA_COUNTRIES[$p['country']]) || $internationalAvailable,
        ], self::PLACES);
    }

    /** Resolve free-text location to a country code, or null if unrecognised/Remote. */
    public static function resolveCountry(string $location): ?string
    {
        $needle = mb_strtolower(trim($location));
        if ($needle === '') {
            return null;
        }

        $best = null;
        $bestLen = 0;
        foreach (self::PLACES as $place) {
            foreach ($place['aliases'] as $alias) {
                if (strlen($alias) > $bestLen && str_contains($needle, $alias)) {
                    $best = $place['country'];
                    $bestLen = strlen($alias);
                }
            }
        }

        return $best;
    }

    /** Generic country display names for error messages — separate from PLACES so a city match doesn't misreport as a country name. */
    private const COUNTRY_NAMES = [
        'ae' => 'the UAE', 'qa' => 'Qatar', 'sa' => 'Saudi Arabia',
        'kw' => 'Kuwait', 'bh' => 'Bahrain', 'om' => 'Oman',
        'my' => 'Malaysia', 'ph' => 'the Philippines', 'id' => 'Indonesia',
        'th' => 'Thailand', 'vn' => 'Vietnam', 'jp' => 'Japan', 'kr' => 'South Korea',
        'cn' => 'China', 'hk' => 'Hong Kong', 'lk' => 'Sri Lanka',
        'bd' => 'Bangladesh', 'pk' => 'Pakistan', 'np' => 'Nepal',
    ];

    public static function isAdzunaSupported(?string $country): bool
    {
        return $country !== null && isset(self::ADZUNA_COUNTRIES[$country]);
    }

    public static function countryLabel(?string $country): string
    {
        if ($country === null) {
            return 'that location';
        }
        return self::ADZUNA_COUNTRIES[$country] ?? self::COUNTRY_NAMES[$country] ?? strtoupper($country);
    }
}
