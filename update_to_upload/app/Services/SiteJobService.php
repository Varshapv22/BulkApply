<?php

namespace App\Services;

use App\Models\FeatureFlag;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Fetches jobs from a specific site the candidate names.
 *
 * Resolution order:
 *   1. A site we have a dedicated reader for (Infopark, Technopark, Cyberpark).
 *   2. A big job platform we can't scrape (Indeed, Naukri, LinkedIn…) → tell the
 *      caller to run an aggregated search and note that results are aggregated.
 *   3. A pasted careers-page URL → generic scrape of schema.org JobPosting data.
 *   4. Anything else (a bare name) → not handled; caller does an aggregated
 *      search using the term as a keyword (good for company names).
 *
 * @return array{jobs: array, error: ?string, handled: bool, platform: ?string}
 */
class SiteJobService
{
    private const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    /**
     * Big aggregator platforms we cannot scrape directly (JS-rendered / bot-blocked
     * / licensed APIs). Adzuna already indexes jobs originating from these, so we
     * fall back to an aggregated search rather than pretending to read the site.
     */
    private const PLATFORMS = [
        'indeed' => 'Indeed', 'naukri' => 'Naukri', 'linkedin' => 'LinkedIn',
        'monster' => 'Monster', 'foundit' => 'Foundit', 'glassdoor' => 'Glassdoor',
        'shine' => 'Shine', 'timesjobs' => 'TimesJobs', 'instahyre' => 'Instahyre',
        'wellfound' => 'Wellfound', 'angellist' => 'Wellfound', 'ziprecruiter' => 'ZipRecruiter',
        'dice' => 'Dice', 'simplyhired' => 'SimplyHired',
    ];

    public function search(string $role, string $site, int $limit = 30): array
    {
        $role = trim($role);
        $site = trim($site);

        if ($site === '') {
            return $this->result(handled: false);
        }

        $lower = Str::lower($site);
        $host  = $this->host($site);

        // 1. Dedicated readers for known Kerala tech-park boards.
        if ((Str::contains($lower, 'infopark') || $host === 'infopark.in') && FeatureFlag::enabled('source.infopark')) {
            $r = (new InfoparkJobService())->search($role, '', $limit);
            return $this->result(jobs: $r['jobs'], error: $r['error'], handled: true);
        }
        if ((Str::contains($lower, 'technopark') || in_array($host, ['technopark.in', 'technopark.org'], true)) && FeatureFlag::enabled('source.technopark')) {
            $r = (new TechnoparkJobService())->search($role, '', $limit);
            return $this->result(jobs: $r['jobs'], error: $r['error'], handled: true);
        }
        if ((Str::contains($lower, 'cyberpark') || in_array($host, ['cyberparks.in', 'cyberparkkerala.org'], true)) && FeatureFlag::enabled('source.cyberpark')) {
            $r = (new CyberparkJobService())->search($role, '', $limit);
            return $this->result(jobs: $r['jobs'], error: $r['error'], handled: true);
        }

        // 2. A big job platform we can't scrape → aggregated fallback (no keyword).
        foreach (self::PLATFORMS as $needle => $label) {
            if (Str::contains($lower, $needle) || ($host && Str::contains($host, $needle))) {
                return $this->result(handled: false, platform: $label);
            }
        }

        // 3. A fetchable URL → generic structured-data scrape.
        $url = $this->asUrl($site);
        if ($url !== null) {
            $r = $this->scrapeGeneric($url, $role, $limit);
            return $this->result(jobs: $r['jobs'], error: $r['error'], handled: $r['handled']);
        }

        // 4. Company name — try to discover and scrape their official careers page.
        $careerResult = $this->tryCompanyCareerPage($site, $role, $limit);
        if ($careerResult['handled']) {
            return $this->result(jobs: $careerResult['jobs'], error: $careerResult['error'], handled: true);
        }

        // Nothing found — caller does an aggregated keyword search.
        return $this->result(handled: false);
    }

    private function result(array $jobs = [], ?string $error = null, bool $handled = false, ?string $platform = null): array
    {
        return ['jobs' => $jobs, 'error' => $error, 'handled' => $handled, 'platform' => $platform];
    }

    /**
     * Scrape schema.org JobPosting entries from an arbitrary careers page.
     */
    private function scrapeGeneric(string $url, string $role, int $limit): array
    {
        $host = $this->host($url) ?: 'that site';

        if (!$this->isSafeUrl($url)) {
            return ['jobs' => [], 'error' => 'That URL is not allowed.', 'handled' => true];
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => self::UA, 'Accept' => 'text/html'])
                ->get($url);

            if (!$response->successful()) {
                return [
                    'jobs'    => [],
                    'error'   => "Couldn't open {$host} (HTTP {$response->status()}).",
                    'handled' => true,
                ];
            }

            $jobs = $this->extractAllJobs($response->body(), $url, $host, $role, $limit);

            if (empty($jobs)) {
                return [
                    'jobs'    => [],
                    'error'   => "Couldn't read job listings directly from {$host} — it may load jobs via JavaScript or not publish structured job data.",
                    'handled' => true,
                ];
            }

            return ['jobs' => $jobs, 'error' => null, 'handled' => true];

        } catch (\Throwable $e) {
            Log::error('Site scrape failed', ['url' => $url, 'error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => "Couldn't fetch jobs from {$host}: {$e->getMessage()}", 'handled' => true];
        }
    }

    /**
     * Pull all schema.org JobPosting objects out of a page's JSON-LD blocks.
     *
     * @return array<int, array{title:string, company:string, location:string, url:string, description:string, posted:?string}>
     */
    private function extractJobPostings(string $html): array
    {
        preg_match_all(
            '#<script[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is',
            $html,
            $matches
        );

        $found = [];
        foreach ($matches[1] as $block) {
            $data = json_decode(trim($block), true);
            if (is_array($data)) {
                $this->collectJobPostings($data, $found);
            }
        }

        return array_map(fn ($jp) => $this->flattenPosting($jp), $found);
    }

    private function collectJobPostings($node, array &$out): void
    {
        if (!is_array($node)) {
            return;
        }

        $type = $node['@type'] ?? null;
        $isJob = (is_string($type) && strtolower($type) === 'jobposting')
            || (is_array($type) && in_array('JobPosting', $type, true));

        if ($isJob) {
            $out[] = $node;
            return; // don't recurse into a posting's own children
        }

        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectJobPostings($value, $out);
            }
        }
    }

    private function flattenPosting(array $jp): array
    {
        $org = $jp['hiringOrganization'] ?? null;
        $company = is_array($org) ? ($org['name'] ?? '') : (is_string($org) ? $org : '');

        $location = '';
        $jl = $jp['jobLocation'] ?? null;
        if (is_array($jl)) {
            $first = isset($jl['@type']) || isset($jl['address']) ? $jl : ($jl[0] ?? []);
            $addr = $first['address'] ?? [];
            if (is_array($addr)) {
                $location = trim(($addr['addressLocality'] ?? '') . ' ' . ($addr['addressRegion'] ?? ''));
            }
        }

        return [
            'title'       => is_string($jp['title'] ?? null) ? trim($jp['title']) : 'Unknown',
            'company'     => is_string($company) ? trim($company) : '',
            'location'    => $location,
            'url'         => is_string($jp['url'] ?? null) ? $jp['url'] : '',
            'description' => is_string($jp['description'] ?? null) ? $jp['description'] : '',
            'posted'      => $jp['datePosted'] ?? null,
        ];
    }

    private function normalizeGeneric(array $jp, string $pageUrl, string $host): array
    {
        $description = strip_tags($jp['description'] ?? '');

        $email = null;
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $description, $m)) {
            $email = $m[0];
        }

        $link = $jp['url'] ?: $pageUrl;
        // The site the candidate pasted is itself the company website.
        $website = 'https://' . $host;

        return [
            'company'         => $jp['company'] ?: $host,
            'job_title'       => $jp['title'] ?: 'Unknown',
            'location'        => $jp['location'] ?: '',
            'recruiter_email' => $email,
            'company_email'   => $email,
            'company_website' => $website,
            'company_phone'   => null,
            'job_url'         => $link,
            'apply_url'       => $link,
            'source'          => $host,
            'apply_type'      => $email ? 'email' : 'link',
            'description'     => Str::limit(trim($description), 200),
            'posted'          => $jp['posted'],
            'employer_logo'   => null,
        ];
    }

    /**
     * Match the posting text against any significant word of the role.
     */
    private function matchesRole(string $haystack, string $role): bool
    {
        if ($role === '') {
            return true;
        }
        $haystack = mb_strtolower($haystack);
        $tokens = array_filter(
            preg_split('/\s+/', mb_strtolower($role)),
            fn ($t) => mb_strlen($t) >= 3
        );
        if (!$tokens) {
            return true;
        }
        foreach ($tokens as $t) {
            if (str_contains($haystack, $t)) {
                return true;
            }
        }
        return false;
    }

    /** Turn user input into a fetchable http(s) URL, or null if it isn't one. */
    private function asUrl(string $input): ?string
    {
        if (Str::startsWith($input, ['http://', 'https://'])) {
            return $input;
        }
        // Bare domain like "careers.company.com/jobs" (has a dot, no spaces).
        if (!str_contains($input, ' ') && preg_match('/^[a-z0-9.-]+\.[a-z]{2,}(\/.*)?$/i', $input)) {
            return 'https://' . $input;
        }
        return null;
    }

    private function host(string $input): ?string
    {
        $url = Str::startsWith($input, ['http://', 'https://']) ? $input : 'https://' . $input;
        $host = parse_url($url, PHP_URL_HOST);
        return $host ? strtolower(preg_replace('/^www\./', '', $host)) : null;
    }

    /**
     * Unified job extractor: tries JSON-LD first, then falls back to HTML heuristics.
     */
    private function extractAllJobs(string $html, string $pageUrl, string $host, string $role, int $limit): array
    {
        // 1. JSON-LD structured data
        $postings = $this->extractJobPostings($html);
        $jobs = [];
        foreach ($postings as $jp) {
            if (!$this->matchesRole($jp['title'] . ' ' . $jp['company'], $role)) {
                continue;
            }
            $jobs[] = $this->normalizeGeneric($jp, $pageUrl, $host);
            if (count($jobs) >= $limit) {
                return $jobs;
            }
        }
        if (!empty($jobs)) {
            return $jobs;
        }

        // 2. Heuristic: <a> links whose URL path looks like an individual job listing
        $heuristic = $this->extractJobsHeuristic($html, $pageUrl, $host);
        foreach ($heuristic as $job) {
            if (!$this->matchesRole($job['job_title'], $role)) {
                continue;
            }
            $jobs[] = $job;
            if (count($jobs) >= $limit) {
                return $jobs;
            }
        }

        return $jobs;
    }

    /**
     * Discover a company's official careers page from a bare name, fetch it
     * concurrently (JSON-LD + HTML heuristics), and return matching job listings.
     *
     * Tries common TLDs (.com, .in, .co.in) and career URL paths (/careers, /jobs…).
     */
    private function tryCompanyCareerPage(string $companyName, string $role, int $limit): array
    {
        $base = $this->guessBaseDomain($companyName);
        if (!$base) {
            return ['jobs' => [], 'error' => null, 'handled' => false];
        }

        $tlds = ['com', 'in', 'co.in'];
        $liveDomain = $this->resolveCompanyDomain($base, $tlds);
        if (!$liveDomain) {
            return ['jobs' => [], 'error' => null, 'handled' => false];
        }

        $careerPaths = ['/careers', '/jobs', '/career', '/careers/openings', '/careers/jobs', '/work-with-us', '/openings'];
        $requests = [];
        foreach ($careerPaths as $path) {
            $url = "https://{$liveDomain}{$path}";
            if ($this->isSafeUrl($url)) {
                $requests[$path] = $url;
            }
        }

        try {
            $pages = Http::pool(function ($pool) use ($requests) {
                $out = [];
                foreach ($requests as $key => $url) {
                    $out[] = $pool->as($key)->timeout(10)->connectTimeout(6)
                        ->withHeaders(['User-Agent' => self::UA, 'Accept' => 'text/html'])
                        ->withOptions(['allow_redirects' => ['max' => 3]])
                        ->get($url);
                }
                return $out;
            });
        } catch (\Throwable $e) {
            Log::warning('Career page fetch failed', ['company' => $companyName, 'error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => null, 'handled' => false];
        }

        foreach ($careerPaths as $path) {
            $resp = $pages[$path] ?? null;
            if (!$resp || $resp instanceof \Throwable || !$resp->successful()) {
                continue;
            }
            $pageUrl = "https://{$liveDomain}{$path}";
            $jobs = $this->extractAllJobs($resp->body(), $pageUrl, $liveDomain, $role, $limit);
            if (!empty($jobs)) {
                return ['jobs' => $jobs, 'error' => null, 'handled' => true];
            }
        }

        return [
            'jobs'    => [],
            'error'   => "Found {$liveDomain} but couldn't read job listings from their careers pages — they may load jobs via JavaScript.",
            'handled' => true,
        ];
    }

    /**
     * Concurrently probe a set of TLD variants and return the first live domain.
     *
     * @param  string[]  $tlds
     */
    private function resolveCompanyDomain(string $base, array $tlds): ?string
    {
        $requests = [];
        foreach ($tlds as $tld) {
            $url = "https://{$base}.{$tld}";
            if ($this->isSafeUrl($url)) {
                $requests[$tld] = $url;
            }
        }
        if (empty($requests)) {
            return null;
        }

        try {
            $responses = Http::pool(function ($pool) use ($requests) {
                $out = [];
                foreach ($requests as $key => $url) {
                    $out[] = $pool->as($key)->timeout(5)->connectTimeout(4)
                        ->withHeaders(['User-Agent' => self::UA])
                        ->head($url);
                }
                return $out;
            });
        } catch (\Throwable $e) {
            return null;
        }

        foreach ($tlds as $tld) {
            $resp = $responses[$tld] ?? null;
            // 405 Method Not Allowed = server is alive but HEAD not supported
            if ($resp && !($resp instanceof \Throwable) && ($resp->successful() || $resp->status() === 405)) {
                return "{$base}.{$tld}";
            }
        }

        return null;
    }

    /**
     * Heuristic HTML extraction: find <a> links whose URL path segment clearly
     * indicates an individual job listing page (not a nav/category link).
     * Used as a fallback when a careers page has no JSON-LD structured data.
     */
    private function extractJobsHeuristic(string $html, string $baseUrl, string $host): array
    {
        $jobs = [];
        $seen = [];
        $baseSchemeHost = 'https://' . $host;

        preg_match_all('#<a\b[^>]+href=["\']([^"\']*)["\'][^>]*>([\s\S]*?)</a>#i', $html, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $href = html_entity_decode(trim($m[1]));
            $text = trim(strip_tags($m[2]));

            // Skip empty, too short, or banner-length text
            if (mb_strlen($text) < 5 || mb_strlen($text) > 150) {
                continue;
            }
            // Skip generic navigation labels
            if (preg_match('/^(home|about|contact|blog|news|products?|services?|login|sign\s*up|register|menu|more|next|prev|back|apply now|click here|read more|learn more|view all|view jobs|see all|all openings|current openings|careers|jobs|openings|our team|team)$/i', $text)) {
                continue;
            }

            // Normalise href to absolute
            if (preg_match('#^https?://#i', $href)) {
                $linkHost = strtolower(preg_replace('/^www\./', '', parse_url($href, PHP_URL_HOST) ?? ''));
                if ($linkHost !== $host) {
                    continue; // off-site link
                }
            } elseif (str_starts_with($href, '/')) {
                $href = $baseSchemeHost . $href;
            } elseif ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            } else {
                $href = $baseSchemeHost . '/' . $href;
            }

            $path = strtolower(parse_url($href, PHP_URL_PATH) ?? '');
            // Accept only links that clearly point at a single job listing
            if (!preg_match('#/(job|jobs|career|careers|opening|openings|position|positions|vacancy|vacancies|role|roles)[/\-_]#', $path)) {
                continue;
            }

            $key = mb_strtolower($text);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $jobs[] = [
                'company'         => $host,
                'job_title'       => $text,
                'location'        => '',
                'recruiter_email' => null,
                'company_email'   => null,
                'company_website' => $baseSchemeHost,
                'company_phone'   => null,
                'job_url'         => $href,
                'apply_url'       => $href,
                'source'          => $host,
                'apply_type'      => 'link',
                'description'     => '',
                'posted'          => null,
                'employer_logo'   => null,
            ];
        }

        return $jobs;
    }

    /**
     * Normalise a company name to a bare domain label suitable for TLD guessing.
     * e.g. "Zoho Corporation Pvt Ltd" → "zoho", "UST Global" → "ust"
     */
    private function guessBaseDomain(string $company): ?string
    {
        $name = strtolower($company);
        $name = preg_replace('/\([^)]*\)/', ' ', $name);          // drop (P), (India) etc.
        $name = preg_replace('/[^a-z0-9 ]+/', ' ', $name);        // keep alnum + spaces

        $stop = ['pvt', 'private', 'ltd', 'limited', 'llp', 'inc', 'incorporated', 'llc', 'corp',
                 'corporation', 'co', 'company', 'technologies', 'technology', 'tech', 'solutions',
                 'solution', 'systems', 'system', 'software', 'softwares', 'labs', 'lab', 'services',
                 'service', 'global', 'india', 'group', 'consulting', 'consultancy', 'infotech',
                 'digital', 'the', 'and'];

        $words = array_values(array_filter(
            preg_split('/\s+/', trim($name)),
            fn ($w) => $w !== '' && !in_array($w, $stop, true)
        ));

        if (empty($words)) {
            return null;
        }

        $base = $words[0];
        if (strlen($base) < 4 && isset($words[1])) {
            $base .= $words[1];   // e.g. "ust" + "global" → but global is in stop-list, so stays "ust"
        }
        $base = preg_replace('/[^a-z0-9]/', '', $base);

        return strlen($base) >= 3 ? $base : null;
    }

    /**
     * SSRF guard: only public http(s) hosts. Blocks localhost, private and
     * reserved IP ranges so a candidate can't point the fetcher at internal hosts.
     */
    private function isSafeUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return false;
        }
        $host = $parts['host'] ?? '';
        if ($host === '' || strtolower($host) === 'localhost') {
            return false;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP) ? $host : gethostbyname($host);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false; // couldn't resolve
        }
        // Reject private / reserved ranges.
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
