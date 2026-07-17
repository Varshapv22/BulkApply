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
    private const MAX_PAGES = 15; // ~300 most-recent jobs scanned
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

            return ['jobs' => $jobs, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('Infopark scrape failed', ['error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => 'Could not fetch Infopark jobs: ' . $e->getMessage()];
        }
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
