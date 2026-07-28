<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * "What is it actually like to work here?" lookup for a company a candidate is
 * about to apply to: the people who work there (LinkedIn profiles) plus where
 * to read about its culture and day-to-day work.
 *
 * LinkedIn blocks automated access and offers no free people-search API, so
 * this service never scrapes LinkedIn. Instead it:
 *
 *   1. Reads the company's OWN website (about / team / leadership pages) and
 *      collects the LinkedIn profiles the company publishes there itself —
 *      real, employer-published links, never guessed.
 *   2. Builds prefilled LinkedIn and review-site search links that the user
 *      opens in their own logged-in browser session (employees, HR/recruiters,
 *      people already doing the role, Glassdoor/AmbitionBox reviews).
 *
 * Nothing is invented: a profile is shown only when the company published it.
 */
class CompanyInsightService
{
    private const CACHE_TTL = 86400;      // 24h — team pages change slowly
    private const MAX_EMPLOYEES = 12;
    private const MAX_DISCOVERED = 6;     // extra team pages followed from the site's own nav
    private const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    /** Pages that most often list the team and link their LinkedIn profiles. */
    private const TEAM_PATHS = ['', '/about', '/about-us', '/team', '/our-team', '/leadership', '/people', '/company', '/careers'];

    /** Words that mark a job title in the text around a profile link. */
    private const ROLE_WORDS = [
        'Co-Founder', 'Cofounder', 'Founder', 'CEO', 'CTO', 'COO', 'CFO', 'CHRO', 'CMO',
        'Vice President', 'President', 'Director', 'Head of', 'Principal', 'Architect',
        'Manager', 'Team Lead', 'Tech Lead', 'Lead', 'Engineer', 'Developer', 'Designer',
        'Analyst', 'Consultant', 'Recruiter', 'Talent Acquisition', 'Human Resources',
        'Scientist', 'Marketing', 'Sales', 'Officer', 'Specialist', 'Executive', 'Intern',
    ];

    /** Anchor text that is a label, not a person's name. */
    private const NON_NAMES = ['linkedin', 'connect', 'follow', 'profile', 'view', 'click here', 'read more', 'link', 'social', 'share'];

    /**
     * Everything the Company Insights panel needs for one company.
     *
     * @param  string       $company  company name as shown on the job
     * @param  string|null  $website  company site if the job already carried one
     * @param  string|null  $role     job title, used to target the people search
     */
    public function forCompany(string $company, ?string $website = null, ?string $role = null): array
    {
        $company = trim(preg_replace('/\s+/', ' ', $company));

        // Only the site crawl is cached; the search links are cheap to rebuild
        // and depend on the role, which changes from job to job.
        $scan = Cache::remember(
            'company_insights:v1:' . md5(mb_strtolower($company) . '|' . mb_strtolower((string) $website)),
            self::CACHE_TTL,
            fn () => $this->scanCompanySite($company, $website)
        );

        $employees = array_slice($scan['employees'], 0, self::MAX_EMPLOYEES);

        return [
            'company'              => $company,
            'role'                 => $role,
            'website'              => $scan['website'],
            'careers_url'          => $scan['careers_url'],
            'linkedin_company_url' => $scan['linkedin_company_url'],
            'employees'            => $employees,
            'people_links'         => $this->peopleLinks($company, $role, $scan['linkedin_company_slug']),
            'culture_links'        => $this->cultureLinks($company),
            'note'                 => $this->note($company, $scan, count($employees)),
        ];
    }

    /**
     * Fetch the company's own about/team pages and pull the LinkedIn profiles
     * it publishes. Returns empty results (never throws) when the site can't
     * be identified or read.
     *
     * @return array{website: ?string, careers_url: ?string, linkedin_company_url: ?string, linkedin_company_slug: ?string, employees: array, scanned: bool}
     */
    private function scanCompanySite(string $company, ?string $website): array
    {
        $empty = [
            'website'               => null,
            'careers_url'           => null,
            'linkedin_company_url'  => null,
            'linkedin_company_slug' => null,
            'employees'             => [],
            'scanned'               => false,
        ];

        $site = $this->normalizeWebsite($website) ?? (new CompanyContactFinder())->websiteFor($company);
        if (!$site) {
            return $empty;
        }

        $base = rtrim($site, '/');
        $requests = [];
        foreach (self::TEAM_PATHS as $path) {
            $requests[$path === '' ? '/' : $path] = $base . $path;
        }

        $employees   = [];
        $companySlug = null;
        $careersUrl  = null;
        $fetched     = [];
        $discovered  = [];

        // Two rounds: the usual paths first, then whatever the site's own
        // navigation calls its team page ("/who-we-are", "/leadership-team"…),
        // which is where most companies actually list people.
        for ($round = 0; $round < 2 && $requests; $round++) {
            $pages = $this->pool($requests);

            foreach ($requests as $key => $url) {
                $fetched[$url] = true;
                $resp = $pages[$key] ?? null;
                if (!$resp || $resp instanceof \Throwable || !$resp->ok()) {
                    continue;
                }
                $html = $resp->body();

                if ($key === '/careers') {
                    $careersUrl = $url;
                }
                $companySlug ??= $this->extractCompanySlug($html);

                foreach ($this->extractProfiles($html, $url) as $profileUrl => $person) {
                    $employees[$profileUrl] = $this->mergePerson($employees[$profileUrl] ?? null, $person);
                }

                if ($round === 0) {
                    $discovered += $this->discoverPeoplePages($html, $base);
                }
            }

            $requests = array_slice(array_diff_key($discovered, $fetched), 0, self::MAX_DISCOVERED, true);
        }

        // Leadership first (they describe the culture best), then alphabetical.
        $employees = array_values(array_map(fn ($p) => Arr::except($p, 'name_from_slug'), $employees));
        usort($employees, function ($a, $b) {
            $rank = fn ($p) => $this->seniorityRank($p['title'] ?? '');
            return [$rank($a), mb_strtolower($a['name'])] <=> [$rank($b), mb_strtolower($b['name'])];
        });

        return [
            'website'               => $site,
            'careers_url'           => $careersUrl,
            'linkedin_company_url'  => $companySlug ? "https://www.linkedin.com/company/{$companySlug}/" : null,
            'linkedin_company_slug' => $companySlug,
            'employees'             => $employees,
            'scanned'               => true,
        ];
    }

    /**
     * Same-site pages that look like they list people, taken from the site's
     * own links — every company names this page differently.
     *
     * @return array<string, string>  url => url
     */
    private function discoverPeoplePages(string $html, string $base): array
    {
        if (!preg_match_all('~<a\b[^>]*href=["\']([^"\'#]+)["\'][^>]*>(.*?)</a>~is', $html, $matches, PREG_SET_ORDER)) {
            return [];
        }

        // Sites routinely link their own pages with a www. prefix the base
        // lacks (or the other way round) — compare the bare host.
        $bare  = fn (?string $h) => preg_replace('/^www\./', '', strtolower((string) $h));
        $host  = $bare(parse_url($base, PHP_URL_HOST));
        $wanted = '/\b(our[\-\s_]?team|team|leadership|management|our[\-\s_]?people|people|who[\-\s_]?we[\-\s_]?are|about[\-\s_]?us|founders|culture|life[\-\s_]?at|meet[\-\s_]?the)\b/i';

        $found = [];
        foreach ($matches as [$_, $href, $label]) {
            $href = trim(html_entity_decode($href, ENT_QUOTES));
            if ($href === '' || Str::startsWith($href, ['mailto:', 'tel:', 'javascript:', 'data:'])) {
                continue;
            }
            if (!preg_match($wanted, $href) && !preg_match($wanted, strip_tags($label))) {
                continue;
            }

            $url = $this->absoluteUrl($href, $base);
            if ($url && $bare(parse_url($url, PHP_URL_HOST)) === $host) {
                $found[$url] = $url;
            }
            if (count($found) >= self::MAX_DISCOVERED * 2) {
                break;
            }
        }

        return $found;
    }

    /** Resolve a page-relative href against the site root; null if off-site or unusable. */
    private function absoluteUrl(string $href, string $base): ?string
    {
        if (Str::startsWith($href, 'http://') || Str::startsWith($href, 'https://')) {
            $url = $href;
        } elseif (Str::startsWith($href, '//')) {
            $url = 'https:' . $href;
        } elseif (Str::startsWith($href, '/')) {
            $url = $base . $href;
        } else {
            $url = $base . '/' . ltrim($href, './');
        }

        $parts = parse_url($url);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            return null;
        }

        return strtok($url, '#');
    }

    /**
     * Pull employee LinkedIn profiles out of one page's HTML.
     *
     * @return array<string, array{name: string, title: ?string, profile_url: string, found_on: string}>
     */
    private function extractProfiles(string $html, string $pageUrl): array
    {
        $pattern = '#<a\b[^>]*href=["\']([^"\']*linkedin\.com/in/[^"\'\s]+)["\'][^>]*>(.*?)</a>#is';
        if (!preg_match_all($pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }

        // Every profile link on the page, as [start, end] byte ranges. A title
        // belongs to whichever link it sits closest to, so neighbouring team
        // cards can't lend each other their job titles.
        $anchors = array_map(fn ($m) => [$m[0][1], $m[0][1] + strlen($m[0][0])], $matches);

        $people = [];
        foreach ($matches as $match) {
            $profileUrl = $this->normalizeProfileUrl(html_entity_decode($match[1][0], ENT_QUOTES));
            if (!$profileUrl) {
                continue;
            }

            $inner = $match[2][0];
            $name = $this->cleanName(strip_tags($inner));                                    // <a>Jane Doe</a>
            if (!$name && preg_match('/alt=["\']([^"\']{3,60})["\']/i', $inner, $alt)) {      // <a><img alt="Jane Doe">
                $name = $this->cleanName($alt[1]);
            }
            if (!$name && preg_match('/(?:title|aria-label)=["\']([^"\']{3,60})["\']/i', $match[0][0], $attr)) {
                $name = $this->cleanName($attr[1]);
            }
            $fromSlug = $name === null;
            $name ??= $this->humanizeSlug($profileUrl);                                       // last resort: the slug
            if (!$name) {
                continue;
            }

            // Job titles sit next to the link in the markup ("Jane Doe — CTO").
            $start  = max(0, $match[0][1] - 300);
            $window = substr($html, $start, 600);
            $own    = [$match[0][1], $match[0][1] + strlen($match[0][0])];

            // A team card usually links the same profile twice — once from the
            // photo (no text) and once from the name — so merge rather than
            // keep the first: the photo link alone would leave us with a
            // slug-derived name and no title.
            $people[$profileUrl] = $this->mergePerson($people[$profileUrl] ?? null, [
                'name'           => $name,
                'name_from_slug' => $fromSlug,
                'title'          => $this->guessTitle($window, $start, $own, $anchors, $name),
                'profile_url'    => $profileUrl,
                'found_on'       => $pageUrl,
            ]);
        }

        return $people;
    }

    /** Keep the richest version of a person seen more than once. */
    private function mergePerson(?array $existing, array $person): array
    {
        if (!$existing) {
            return $person;
        }
        if ($existing['name_from_slug'] && !$person['name_from_slug']) {
            $existing['name'] = $person['name'];
            $existing['name_from_slug'] = false;
        }
        $existing['title'] ??= $person['title'];

        return $existing;
    }

    /** Canonical https://www.linkedin.com/in/{slug} URL, or null if not a real profile link. */
    private function normalizeProfileUrl(string $href): ?string
    {
        if (!preg_match('#linkedin\.com/in/([A-Za-z0-9\-_%\p{L}]{3,100})#u', $href, $m)) {
            return null;
        }
        $slug = trim(rawurldecode($m[1]), '-');
        if ($slug === '' || in_array(mb_strtolower($slug), ['me', 'you', 'in', 'profile', 'username', 'yourprofile'], true)) {
            return null;
        }

        return 'https://www.linkedin.com/in/' . $slug;
    }

    /** The company's own LinkedIn page slug, if the site links to it. */
    private function extractCompanySlug(string $html): ?string
    {
        if (!preg_match('#linkedin\.com/(?:company|school)/([A-Za-z0-9\-_%\.]{2,100})#i', $html, $m)) {
            return null;
        }
        $slug = trim(rawurldecode($m[1]), '/-');

        return $slug !== '' ? $slug : null;
    }

    /** Keep only strings that read like a person's name. */
    private function cleanName(string $raw): ?string
    {
        $name = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($raw), ENT_QUOTES)));
        $name = trim($name, " \t\n\r\0\x0B|-–—•·:,");

        if (mb_strlen($name) < 3 || mb_strlen($name) > 60) {
            return null;
        }
        if (!preg_match('/\p{L}/u', $name) || str_contains($name, 'http') || str_contains($name, '@')) {
            return null;
        }
        $lower = mb_strtolower($name);
        foreach (self::NON_NAMES as $junk) {
            if (str_contains($lower, $junk)) {
                return null;
            }
        }
        // Names are short; a sentence that happens to wrap the link is not one.
        return str_word_count($name) <= 5 ? $name : null;
    }

    /** "john-doe-84a21b" -> "John Doe" (derived from the profile slug, not invented). */
    private function humanizeSlug(string $profileUrl): ?string
    {
        $slug  = basename(parse_url($profileUrl, PHP_URL_PATH) ?: '');
        $parts = array_filter(
            preg_split('/[-_]+/', $slug),
            fn ($p) => mb_strlen($p) > 1 && !preg_match('/\d/', $p)
        );
        if (empty($parts)) {
            return null;
        }

        return Str::title(implode(' ', array_slice($parts, 0, 3)));
    }

    /**
     * Best-effort job title from the markup around a profile link. Team-page
     * markup keeps the title in its own element next to the name, so the text
     * nodes are scored individually (nearest node that reads like a role wins)
     * rather than flattening the whole window — flattening merges neighbouring
     * cards and yields another person's title.
     *
     * @param  int    $windowStart  offset of $windowHtml within the full page
     * @param  int[]  $own          [start, end] of this person's link
     * @param  array  $anchors      [start, end] of every profile link on the page
     */
    private function guessTitle(string $windowHtml, int $windowStart, array $own, array $anchors, string $name): ?string
    {
        $best = null;
        $bestDistance = PHP_INT_MAX;

        // "CEO &amp; Managing<br>Director" is one title, not two — blank the
        // line breaks out, padded to the same length so offsets stay valid.
        $windowHtml = preg_replace_callback('~</?br\s*/?>~i', fn ($m) => str_repeat(' ', strlen($m[0])), $windowHtml);

        foreach (preg_split('/<[^>]*>/', $windowHtml, -1, PREG_SPLIT_OFFSET_CAPTURE) as [$piece, $offset]) {
            $text = trim(preg_replace('/\s+/', ' ', html_entity_decode($piece, ENT_QUOTES)), " \t\n\r|-–—•·:,");
            if (mb_strlen($text) < 2 || mb_strlen($text) > 70 || stripos($text, $name) !== false) {
                continue;
            }
            if (!$this->looksLikeRole($text)) {
                continue;
            }

            $position = $windowStart + $offset;
            $distance = $this->distanceTo($position, $own);
            if ($distance >= $bestDistance) {
                continue;
            }
            // Another person's link is nearer — that title is theirs, not ours.
            foreach ($anchors as $other) {
                if ($other !== $own && $this->distanceTo($position, $other) < $distance) {
                    continue 2;
                }
            }

            $best = Str::limit($text, 60, '');
            $bestDistance = $distance;
        }

        return $best;
    }

    /** Gap in bytes between a text node and an [start, end] link range. */
    private function distanceTo(int $position, array $anchor): int
    {
        [$start, $end] = $anchor;

        return $position < $start ? $start - $position : max(0, $position - $end);
    }

    private function looksLikeRole(string $text): bool
    {
        foreach (self::ROLE_WORDS as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Leaders first when ordering the people list, then the folks a candidate
     * most wants to reach (VPs/heads, managers, recruiters).
     */
    private function seniorityRank(string $title): int
    {
        // "president" must not swallow "senior vice president" — hence the
        // lookbehind and the rank order.
        $tiers = [
            '/\b(founder|co-?founder|ceo|cto|coo|cfo|chro|cmo|chief|managing director|(?<!vice )president)\b/i',
            '/\b(vice president|vp|head of|director)\b/i',
            '/\b(manager|lead|principal|architect)\b/i',
            '/\b(recruiter|recruitment|talent acquisition|human resources|hr)\b/i',
        ];

        foreach ($tiers as $rank => $pattern) {
            if (preg_match($pattern, $title)) {
                return $rank;
            }
        }

        return 9;
    }

    /**
     * Prefilled LinkedIn searches the user runs in their own session — the
     * supported way to browse people at a company.
     */
    private function peopleLinks(string $company, ?string $role, ?string $companySlug): array
    {
        $q = fn (string $terms) => 'https://www.linkedin.com/search/results/people/?keywords=' . rawurlencode($terms);

        $links = [];

        $links[] = $companySlug
            ? ['label' => 'All employees on LinkedIn', 'url' => "https://www.linkedin.com/company/{$companySlug}/people/", 'hint' => "Everyone who lists {$company} as their employer"]
            : ['label' => 'All employees on LinkedIn', 'url' => $q($company), 'hint' => "People who mention {$company} on their profile"];

        if (filled($role)) {
            $links[] = [
                'label' => 'People already doing this role',
                'url'   => $q("{$company} {$role}"),
                'hint'  => 'Ask them what the day-to-day actually looks like',
            ];
        }

        $links[] = [
            'label' => 'HR & recruiters',
            'url'   => $q("{$company} HR recruiter talent acquisition"),
            'hint'  => 'Best people to message about an open vacancy',
        ];
        $links[] = [
            'label' => 'Hiring managers & leads',
            'url'   => $q("{$company} hiring manager team lead engineering manager"),
            'hint'  => 'Who you would likely report to',
        ];
        $links[] = [
            'label' => 'Public profiles via Google',
            'url'   => 'https://www.google.com/search?q=' . rawurlencode("site:linkedin.com/in \"{$company}\""),
            'hint'  => 'Works without being logged in to LinkedIn',
        ];

        if ($companySlug) {
            $links[] = [
                'label' => 'Company posts & updates',
                'url'   => "https://www.linkedin.com/company/{$companySlug}/posts/",
                'hint'  => 'How the company talks about its work and team',
            ];
        }

        return $links;
    }

    /** Where to read about pay, management and day-to-day atmosphere. */
    private function cultureLinks(string $company): array
    {
        return [
            [
                'label' => 'Glassdoor reviews',
                'url'   => 'https://www.glassdoor.com/Search/results.htm?keyword=' . rawurlencode($company),
                'hint'  => 'Ratings, management, interview experiences',
            ],
            [
                'label' => 'AmbitionBox reviews',
                'url'   => 'https://www.ambitionbox.com/reviews?title=' . rawurlencode($company),
                'hint'  => 'Detailed India-focused employee reviews',
            ],
            [
                'label' => 'Indeed company reviews',
                'url'   => 'https://www.indeed.com/companies/search?q=' . rawurlencode($company),
                'hint'  => 'Work-life balance, pay and benefits',
            ],
            [
                'label' => 'Work culture on the web',
                'url'   => 'https://www.google.com/search?q=' . rawurlencode("{$company} employee reviews work culture office"),
                'hint'  => 'News, blogs and discussion threads',
            ],
        ];
    }

    private function note(string $company, array $scan, int $found): ?string
    {
        if (!$scan['scanned']) {
            return "We couldn't identify {$company}'s own website, so there are no employer-published profiles to show. The LinkedIn searches below still work.";
        }
        // The site is matched from the company name, so name it — that way a
        // wrong match (two companies sharing a name) is obvious, not silent.
        $domain = preg_replace('#^https?://(www\.)?#', '', (string) $scan['website']);

        if ($found === 0) {
            return "No employee LinkedIn profiles are published on {$domain}. Use the searches below to find people at {$company} directly on LinkedIn.";
        }

        return "Profiles published by the company itself on {$domain}.";
    }

    /**
     * Concurrent GET for key => url, skipping unsafe hosts.
     *
     * @return array<string, mixed>
     */
    private function pool(array $requests): array
    {
        try {
            return Http::pool(function ($pool) use ($requests) {
                $out = [];
                foreach ($requests as $key => $url) {
                    $out[] = $pool->as($key)->timeout(8)->connectTimeout(5)
                        ->withHeaders(['User-Agent' => self::UA, 'Accept' => 'text/html'])
                        ->withOptions(['allow_redirects' => ['max' => 3]])
                        ->get($url);
                }
                return $out;
            });
        } catch (\Throwable $e) {
            Log::warning('Company insight fetch failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Validate a caller-supplied website before we fetch it. Unlike the
     * name-derived domains in CompanyContactFinder this value reaches us from
     * the browser, so the host is resolved and private/reserved IPs rejected
     * (SSRF guard).
     */
    private function normalizeWebsite(?string $website): ?string
    {
        $website = trim((string) $website);
        if ($website === '') {
            return null;
        }
        if (!Str::startsWith($website, ['http://', 'https://'])) {
            $website = 'https://' . $website;
        }

        $parts = parse_url($website);
        $host  = strtolower($parts['host'] ?? '');
        if (!$parts || $host === '' || !str_contains($host, '.') || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        $ip = gethostbyname($host);
        if ($ip === $host || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        return 'https://' . $host;
    }
}
