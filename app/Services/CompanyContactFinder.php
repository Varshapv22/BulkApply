<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Best-effort company contact finder for jobs that arrive without an email
 * (e.g. Adzuna whole-web results). Fully free, no API keys:
 *
 *   1. Guess likely domains from the company name (name.com/.in/.co.in/.io).
 *   2. Fetch the live homepage + contact/careers pages concurrently.
 *   3. Show the website only when the company name actually appears on it, and
 *      extract a REAL same-domain email (never a guessed/fabricated address).
 */
class CompanyContactFinder
{
    private const MAX_JOBS = 12;   // distinct companies looked up per search
    private const TLDS = ['com', 'in', 'co.in', 'io'];
    private const PATHS = ['', '/contact', '/contact-us', '/careers'];
    private const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    private const ROLE_PREFIXES = ['careers', 'career', 'jobs', 'job', 'hr', 'hiring', 'recruit', 'recruitment', 'talent', 'work', 'apply', 'hello', 'contact', 'info', 'reach', 'connect'];
    private const JUNK_HOSTS = ['sentry', 'wixpress', 'godaddy', 'example.com', 'domain.com', 'yourdomain', 'company.com',
        'email.com', 'test.com', 'cloudflare', 'gstatic', 'googleapis', 'schema.org', 'w3.org', 'jsdelivr', 'bootstrapcdn', 'wordpress'];
    // Placeholder local-parts that appear in site templates, never real inboxes.
    private const JUNK_PREFIXES = ['you', 'name', 'yourname', 'your', 'firstname', 'lastname', 'user', 'username', 'example', 'test', 'someone', 'abc', 'xyz'];

    /**
     * Enrich jobs (in place) that have no company_email. Only touches jobs
     * whose company name yields a live website with a discoverable email.
     */
    public function enrich(array &$jobs): void
    {
        // Distinct companies still missing an email OR website.
        $targets = [];
        $seen = [];
        foreach ($jobs as $i => $job) {
            if (!empty($job['company_email']) && !empty($job['company_website'])) {
                continue;
            }
            $company = trim($job['company'] ?? '');
            if ($company === '' || strtolower($company) === 'unknown') {
                continue;
            }
            $key = mb_strtolower($company);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $targets[$i] = $company;
            if (count($targets) >= self::MAX_JOBS) {
                break;
            }
        }
        if (empty($targets)) {
            return;
        }

        // Guess each company's domain from its name and keep the ones that resolve.
        $bases = [];
        foreach ($targets as $i => $company) {
            if ($base = $this->baseDomainName($company)) {
                $bases[$i] = $base;
            }
        }
        $live = $this->resolveLiveDomains($bases);
        if (empty($live)) {
            return;
        }

        // Fetch homepage + contact/careers of each site for an email.
        $requests = [];
        foreach ($live as $i => $domain) {
            foreach (self::PATHS as $p) {
                $requests["{$i}|{$domain}{$p}"] = "https://{$domain}{$p}";
            }
        }
        $pages = $this->pool($requests);

        foreach ($live as $i => $domain) {
            // Confirm this really is the company's own site: the domain's name
            // (derived from the company name) should appear on its homepage.
            // This is lenient enough for messy names ("Fingent, a Great Place
            // to Work…") yet still rejects a coincidental domain.
            $home = $pages["{$i}|{$domain}"] ?? null;
            $homeHtml = ($home && !($home instanceof \Throwable) && $home->ok()) ? $home->body() : '';
            if ($homeHtml === '' || !$this->verifyCompany($homeHtml, $domain)) {
                continue;
            }

            if (empty($jobs[$i]['company_website'])) {
                $jobs[$i]['company_website'] = 'https://' . $domain;
            }

            if (!empty($jobs[$i]['company_email'])) {
                continue;
            }

            // Read a REAL published email off the site (same-domain only). The
            // domain is verified above, so a published address is genuinely theirs.
            $email = null;
            foreach (self::PATHS as $p) {
                $resp = $pages["{$i}|{$domain}{$p}"] ?? null;
                if (!$resp || $resp instanceof \Throwable || !$resp->ok()) {
                    continue;
                }
                $email = $this->extractEmail($resp->body(), $domain);
                if ($email) {
                    break;
                }
            }
            if ($email) {
                $jobs[$i]['company_email']   = $email;
                $jobs[$i]['recruiter_email'] = $email;
                $jobs[$i]['apply_type']      = 'email';
            }
        }
    }

    /**
     * Confirm a guessed domain belongs to the company by checking its root
     * label (e.g. "fingent" from fingent.com) appears on the fetched homepage.
     */
    private function verifyCompany(string $html, string $domain): bool
    {
        $root = explode('.', preg_replace('/^www\./', '', $domain))[0];
        if (mb_strlen($root) < 4) {
            return false;
        }
        return str_contains(mb_strtolower(strip_tags($html)), $root);
    }

    /**
     * @param  array<int,string>  $targets  jobIndex => base domain name
     * @return array<int,string>  jobIndex => live domain (host)
     */
    private function resolveLiveDomains(array $targets): array
    {
        $requests = [];
        foreach ($targets as $i => $base) {
            foreach (self::TLDS as $tld) {
                $host = "{$base}.{$tld}";
                $requests["{$i}|{$host}"] = "https://{$host}";
            }
        }

        $responses = $this->pool($requests, 6);

        $live = [];
        foreach ($targets as $i => $base) {
            foreach (self::TLDS as $tld) {
                if (isset($live[$i])) {
                    break;
                }
                $host = "{$base}.{$tld}";
                $resp = $responses["{$i}|{$host}"] ?? null;
                if ($resp && !($resp instanceof \Throwable) && $resp->successful()) {
                    $live[$i] = $host;
                }
            }
        }
        return $live;
    }

    /**
     * Concurrent GET for a map of key => url, skipping unsafe hosts.
     *
     * @return array<string, mixed>
     */
    private function pool(array $requests, int $timeout = 8): array
    {
        try {
            return Http::pool(function ($pool) use ($requests, $timeout) {
                $out = [];
                foreach ($requests as $key => $url) {
                    if (!$this->isSafeUrl($url)) {
                        continue;
                    }
                    $out[] = $pool->as($key)->timeout($timeout)->connectTimeout(5)
                        ->withHeaders(['User-Agent' => self::UA, 'Accept' => 'text/html'])
                        ->withOptions(['allow_redirects' => ['max' => 3]])
                        ->get($url);
                }
                return $out;
            });
        } catch (\Throwable $e) {
            Log::warning('Contact finder pool failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /** Normalise a company name into a bare domain label, or null if unusable. */
    private function baseDomainName(string $company): ?string
    {
        $name = strtolower($company);
        $name = preg_replace('/\([^)]*\)/', ' ', $name);          // drop (P), (India) etc.
        $name = preg_replace('/[^a-z0-9 ]+/', ' ', $name);        // keep alnum + space

        $stop = ['pvt', 'private', 'ltd', 'limited', 'llp', 'inc', 'incorporated', 'llc', 'corp',
                 'corporation', 'co', 'company', 'technologies', 'technology', 'tech', 'solutions',
                 'solution', 'systems', 'system', 'software', 'softwares', 'labs', 'lab', 'services',
                 'service', 'global', 'india', 'group', 'consulting', 'consultancy', 'infotech',
                 'digital', 'the', 'and'];

        $words = array_values(array_filter(preg_split('/\s+/', trim($name)), fn ($w) => $w !== '' && !in_array($w, $stop, true)));
        if (empty($words)) {
            return null;
        }

        // Join the first up-to-two meaningful words (e.g. "2base technologies" -> "2base").
        $base = $words[0];
        if (strlen($base) < 4 && isset($words[1])) {
            $base .= $words[1];
        }
        $base = preg_replace('/[^a-z0-9]/', '', $base);

        return strlen($base) >= 3 ? $base : null;
    }

    /** Extract the best real email from page HTML, preferring the site's own domain. */
    private function extractEmail(string $html, string $domain): ?string
    {
        $text = html_entity_decode($html, ENT_QUOTES);
        if (!preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            return null;
        }

        $root = preg_replace('/^www\./', '', $domain);
        $candidates = [];
        foreach (array_unique($m[0]) as $email) {
            $email  = rtrim(strtolower($email), '.');
            $host   = substr(strrchr($email, '@'), 1);
            $prefix = strstr($email, '@', true);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            if (preg_match('/\.(png|jpg|jpeg|gif|svg|webp|css|js)$/i', $email)) {
                continue;
            }
            // Only trust an email on the site's OWN domain — avoids third-party
            // addresses and reduces wrong-company false positives.
            if (!str_contains($host, $root)) {
                continue;
            }
            foreach (self::JUNK_HOSTS as $junk) {
                if (str_contains($host, $junk)) {
                    continue 2;
                }
            }
            if (in_array($prefix, self::JUNK_PREFIXES, true) || str_contains($prefix, 'firstname') || str_contains($prefix, 'lastname')) {
                continue;
            }
            $candidates[] = $email;
        }
        if (empty($candidates)) {
            return null;
        }

        // Prefer role-based addresses (careers@, hr@, jobs@…).
        usort($candidates, fn ($a, $b) => $this->roleScore($b) <=> $this->roleScore($a));

        return $candidates[0];
    }

    private function roleScore(string $email): int
    {
        $prefix = strstr($email, '@', true);
        foreach (self::ROLE_PREFIXES as $r) {
            if (str_starts_with($prefix, $r)) {
                return 10 - array_search($r, self::ROLE_PREFIXES, true);
            }
        }
        return 0;
    }

    /**
     * SSRF guard — public https hosts only. Domains here are derived from
     * company names (not user input), so a name-level check is enough and,
     * crucially, avoids a blocking DNS lookup per candidate (curl resolves
     * concurrently inside the pool instead).
     */
    private function isSafeUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https') {
            return false;
        }
        $host = strtolower($parts['host'] ?? '');
        if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return false; // no IP-literal or localhost targets
        }
        return true;
    }
}
