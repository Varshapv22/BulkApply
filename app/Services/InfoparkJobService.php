<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scrapes the Infopark (Kochi) job board at https://infopark.in/companies-job.
 * The board is a plain HTML table, paginated 20 jobs/page (~23 pages).
 * We fetch pages concurrently, then filter by role/keyword.
 */
class InfoparkJobService
{
    private const BASE = 'https://infopark.in/companies-job';
    private const MAX_PAGES = 15;  // ~300 most-recent jobs scanned
    private const MAX_ENRICH = 24; // detail pages fetched per search (concurrent)
    private const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    /**
     * @return array{jobs: array, error: ?string}
     */
    public function search(string $role, string $keyword = '', int $limit = 30): array
    {
        $role    = trim($role);
        $keyword = trim($keyword);

        try {
            $pages = range(1, self::MAX_PAGES);

            $responses = Http::pool(fn ($pool) => array_map(
                fn ($p) => $pool->as((string) $p)
                    ->timeout(15)
                    ->withHeaders(['User-Agent' => self::UA])
                    ->get(self::BASE, ['page' => $p]),
                $pages
            ));

            $rows = [];
            foreach ($pages as $p) {
                $resp = $responses[(string) $p] ?? null;
                if (!$resp || $resp instanceof \Throwable || !$resp->ok()) {
                    continue;
                }
                $rows = array_merge($rows, $this->parseRows($resp->body()));
            }

            if (empty($rows)) {
                return ['jobs' => [], 'error' => 'Could not read any jobs from the Infopark board right now. Please try again shortly.'];
            }

            $jobs = [];
            foreach ($rows as $row) {
                if (!$this->matches($row, $role, $keyword)) {
                    continue;
                }
                $jobs[] = $this->normalize($row);
                if (count($jobs) >= $limit) {
                    break;
                }
            }

            $this->enrichContacts($jobs);

            return ['jobs' => $jobs, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('Infopark scrape failed', ['error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => 'Could not fetch Infopark jobs: ' . $e->getMessage()];
        }
    }

    /**
     * Fetch each job's detail page concurrently and extract the company's
     * application email (mentioned in the description) and website (derived
     * from the email domain, or an external link on the page).
     */
    private function enrichContacts(array &$jobs): void
    {
        $targets = array_slice(array_keys($jobs), 0, self::MAX_ENRICH);
        if (empty($targets)) {
            return;
        }

        try {
            $responses = Http::pool(fn ($pool) => array_map(
                fn ($i) => $pool->as((string) $i)->timeout(15)
                    ->withHeaders(['User-Agent' => self::UA])->get($jobs[$i]['job_url']),
                $targets
            ));
        } catch (\Throwable $e) {
            Log::warning('Infopark enrich failed', ['error' => $e->getMessage()]);
            return;
        }

        foreach ($targets as $i) {
            $resp = $responses[(string) $i] ?? null;
            if (!$resp || $resp instanceof \Throwable || !$resp->ok()) {
                continue;
            }
            [$email, $website] = $this->extractContact($resp->body());
            if ($email) {
                $jobs[$i]['company_email']   = $email;
                $jobs[$i]['recruiter_email'] = $email;
                $jobs[$i]['apply_type']      = 'email';
            }
            if ($website) {
                $jobs[$i]['company_website'] = $website;
            }
        }
    }

    /**
     * @return array{0: ?string, 1: ?string} [email, website]
     */
    private function extractContact(string $html): array
    {
        $text = strip_tags($html);

        $email = null;
        if (preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $m)) {
            foreach ($m[0] as $candidate) {
                // Skip Infopark's own contact address — we want the company's.
                if (!str_contains(strtolower($candidate), 'infopark.in')) {
                    $email = $candidate;
                    break;
                }
            }
        }

        // The company website is most reliably the email's domain.
        $website = null;
        if ($email) {
            $domain = strtolower(substr(strrchr($email, '@'), 1));
            $freeMail = ['gmail', 'yahoo', 'outlook', 'hotmail', 'rediff', 'live.com', 'icloud'];
            $isFree = false;
            foreach ($freeMail as $f) {
                if (str_contains($domain, $f)) { $isFree = true; break; }
            }
            if ($domain && !$isFree && !str_contains($domain, 'infopark')) {
                $website = 'https://' . $domain;
            }
        }

        return [$email, $website];
    }

    /**
     * Parse the job-list table into raw rows.
     *
     * @return array<int, array{posted:string, title:string, company:string, lastdate:string, url:string}>
     */
    private function parseRows(string $html): array
    {
        $rows = [];

        $prev = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        $xp  = new \DOMXPath($doc);
        $trs = $xp->query('//*[@id="job-list"]//tbody/tr');

        foreach ($trs as $tr) {
            $tds = $xp->query('.//td', $tr);
            if ($tds->length < 4) {
                continue;
            }
            $linkNode = $xp->query('.//a/@href', $tr)->item(0);

            $rows[] = [
                'posted'   => trim($tds->item(0)->textContent),
                'title'    => trim($tds->item(1)->textContent),
                'company'  => trim($tds->item(2)->textContent),
                'lastdate' => trim($tds->item(3)->textContent),
                'url'      => $linkNode ? trim($linkNode->textContent) : self::BASE,
            ];
        }

        return $rows;
    }

    /**
     * Match a row against the role (any significant word) and optional keyword.
     */
    private function matches(array $row, string $role, string $keyword): bool
    {
        $hay = mb_strtolower($row['title'] . ' ' . $row['company']);

        if ($keyword !== '' && !str_contains($hay, mb_strtolower($keyword))) {
            return false;
        }

        if ($role !== '') {
            $tokens = array_filter(
                preg_split('/\s+/', mb_strtolower($role)),
                fn ($t) => mb_strlen($t) >= 3
            );
            if ($tokens) {
                foreach ($tokens as $t) {
                    if (str_contains($hay, $t)) {
                        return true;
                    }
                }
                return false;
            }
        }

        return true;
    }

    private function normalize(array $row): array
    {
        return [
            'company'         => $row['company'] ?: 'Unknown',
            'job_title'       => $row['title'] ?: 'Unknown',
            'location'        => 'Infopark, Kochi, Kerala',
            'recruiter_email' => null,
            'company_email'   => null,
            'company_website' => null,
            'company_phone'   => null,
            'job_url'         => $row['url'],
            'apply_url'       => $row['url'],
            'source'          => 'Infopark',
            'apply_type'      => 'link',
            'description'     => trim("Posted {$row['posted']} · Apply by {$row['lastdate']}", ' ·'),
            'posted'          => $row['posted'],
            'employer_logo'   => null,
        ];
    }
}
