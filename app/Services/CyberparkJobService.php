<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Reads jobs from the Cyberpark (Kozhikode) board at cyberparks.in.
 * The site is WordPress + WP Job Manager; its AJAX endpoint is WAF-blocked to
 * bots, but the job_listing custom-post-type RSS feed is public. The feed is
 * capped by WordPress at the 10 most-recent postings.
 */
class CyberparkJobService
{
    private const FEED = 'https://cyberparks.in/?post_type=job_listing&feed=rss2';
    private const UA = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    /**
     * @return array{jobs: array, error: ?string}
     */
    public function search(string $role, string $keyword = '', int $limit = 30): array
    {
        $role    = trim($role);
        $keyword = trim($keyword);

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => self::UA, 'Accept' => 'application/rss+xml, application/xml'])
                ->get(self::FEED);

            if (!$response->successful()) {
                return ['jobs' => [], 'error' => "Could not reach the Cyberpark board (HTTP {$response->status()})."];
            }

            $items = $this->parseFeed($response->body());
            if (empty($items)) {
                return ['jobs' => [], 'error' => 'No current openings were published on the Cyberpark board.'];
            }

            $jobs = [];
            foreach ($items as $item) {
                if (!$this->matches($item, $role, $keyword)) {
                    continue;
                }
                $jobs[] = $this->normalize($item);
                if (count($jobs) >= $limit) {
                    break;
                }
            }

            $this->enrichContacts($jobs);

            return ['jobs' => $jobs, 'error' => null];

        } catch (\Throwable $e) {
            Log::error('Cyberpark fetch failed', ['error' => $e->getMessage()]);
            return ['jobs' => [], 'error' => 'Could not fetch Cyberpark jobs: ' . $e->getMessage()];
        }
    }

    /**
     * Fetch each job page concurrently and pull the WP Job Manager application
     * email (entity-encoded mailto) and the company website link.
     */
    private function enrichContacts(array &$jobs): void
    {
        $targets = array_keys($jobs);
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
            Log::warning('Cyberpark enrich failed', ['error' => $e->getMessage()]);
            return;
        }

        foreach ($targets as $i) {
            $resp = $responses[(string) $i] ?? null;
            if (!$resp || $resp instanceof \Throwable || !$resp->ok()) {
                continue;
            }
            $html = $resp->body();

            // Application email — inside a mailto link, HTML-entity encoded.
            if (preg_match('/href="mailto:([^"?]+)/i', $html, $m)) {
                $email = html_entity_decode($m[1], ENT_QUOTES);
                if (filter_var($email, FILTER_VALIDATE_EMAIL) && !str_contains(strtolower($email), 'cyberparks.in')) {
                    $jobs[$i]['company_email']   = $email;
                    $jobs[$i]['recruiter_email'] = $email;
                    $jobs[$i]['apply_type']      = 'email';
                }
            }
            // Company website — <a class="website" href="...">.
            if (preg_match('/class="website"[^>]*href="(https?:\/\/[^"]+)"/i', $html, $m)) {
                $jobs[$i]['company_website'] = $m[1];
            }
        }
    }

    /**
     * @return array<int, array{title:string, link:string, description:string, posted:?string, company:string}>
     */
    private function parseFeed(string $xml): array
    {
        $prev = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if ($doc === false) {
            return [];
        }

        $items = [];
        foreach ($doc->channel->item ?? [] as $node) {
            $link  = trim((string) $node->link);
            $title = trim((string) $node->title);
            $desc  = trim(strip_tags((string) $node->description));

            $items[] = [
                'title'       => $title,
                'link'        => $link,
                'description' => $desc,
                'posted'      => trim((string) $node->pubDate) ?: null,
                'company'     => $this->companyFromLink($link),
            ];
        }

        return $items;
    }

    /**
     * WP Job Manager slugs look like "{company}-govt-cyber-park-calicut-{title}".
     * Pull the company portion before the "cyber-park" marker.
     */
    private function companyFromLink(string $link): string
    {
        $slug = trim(parse_url($link, PHP_URL_PATH) ?: '', '/');
        $slug = preg_replace('#^job/#', '', $slug);

        $marker = preg_split('/-(govt-)?cyber-?park/i', $slug, 2)[0] ?? $slug;
        $marker = trim(str_replace('-', ' ', $marker));

        return $marker !== '' ? Str::title($marker) : 'Cyberpark';
    }

    private function matches(array $item, string $role, string $keyword): bool
    {
        $hay = mb_strtolower($item['title'] . ' ' . $item['description'] . ' ' . $item['company']);

        if ($keyword !== '' && !str_contains($hay, mb_strtolower($keyword))) {
            return false;
        }
        if ($role !== '') {
            $tokens = array_filter(preg_split('/\s+/', mb_strtolower($role)), fn ($t) => mb_strlen($t) >= 3);
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

    private function normalize(array $item): array
    {
        $email = null;
        if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $item['description'], $m)) {
            $email = $m[0];
        }

        return [
            'company'         => $item['company'],
            'job_title'       => $item['title'] ?: 'Unknown',
            'location'        => 'Cyberpark, Kozhikode, Kerala',
            'recruiter_email' => $email,
            'company_email'   => $email,
            'company_website' => null,
            'company_phone'   => null,
            'job_url'         => $item['link'],
            'apply_url'       => $item['link'],
            'source'          => 'Cyberpark',
            'apply_type'      => $email ? 'email' : 'link',
            'description'     => Str::limit($item['description'], 200),
            'posted'          => $item['posted'],
            'employer_logo'   => null,
        ];
    }
}
