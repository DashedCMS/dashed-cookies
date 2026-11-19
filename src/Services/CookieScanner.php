<?php

namespace Dashed\DashedCookies\Services;

use Throwable;
use Illuminate\Support\Facades\Http;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCookies\Models\Cookie;
use Dashed\DashedCore\Models\Customsetting;

/**
 * Scant een site zonder headless browser: haalt de homepage en een handvol
 * interne pagina's op, leest de Set-Cookie-headers, en zoekt in de HTML naar
 * de handtekeningen van bekende diensten (ook scripts die op toestemming
 * wachten staan als text/plain in de HTML en worden dus gevonden).
 *
 * Wat hij niet ziet: een cookie die JavaScript zet zonder dat het script
 * van een bekende dienst is. Daarvoor is de catalogus en het handmatig
 * toevoegen.
 */
class CookieScanner
{
    public function __construct(protected CookieSynchronizer $synchronizer)
    {
    }

    public function scan(string $siteId): array
    {
        return \Dashed\DashedCookies\Classes\CookieCache::defer(fn () => $this->run($siteId));
    }

    protected function run(string $siteId): array
    {
        $base = rtrim(Sites::url('', $siteId), '/');
        $maxPages = max(1, (int) config('dashed-cookies.scan.max_pages', 10));
        $host = parse_url($base, PHP_URL_HOST);

        $queue = [$base.'/'];
        $visited = [];
        $services = [];
        $found = [];
        $errors = [];

        while ($queue && count($visited) < $maxPages) {
            $url = array_shift($queue);

            if (isset($visited[$url])) {
                continue;
            }

            $visited[$url] = true;

            try {
                $response = Http::timeout((int) config('dashed-cookies.scan.timeout', 15))
                    ->withHeaders([
                        'User-Agent' => 'DashedCookieScanner/1.0',
                        'Accept' => 'text/html',
                    ])
                    ->get($url);
            } catch (Throwable $e) {
                $errors[] = $url.': '.$e->getMessage();

                continue;
            }

            foreach ($response->headers()['Set-Cookie'] ?? $response->headers()['set-cookie'] ?? [] as $header) {
                $name = trim((string) strtok((string) $header, '='));

                if ($name !== '') {
                    $found[$name] ??= $url;
                }
            }

            $html = (string) $response->body();
            $services = array_merge($services, ServiceCatalog::detectInHtml($html));

            if (count($visited) === 1) {
                foreach ($this->internalLinks($html, $base, (string) $host) as $link) {
                    if (! isset($visited[$link]) && ! in_array($link, $queue, true)) {
                        $queue[] = $link;
                    }
                }
            }
        }

        $unknown = [];
        $newCookies = [];
        $previousServices = CookieSynchronizer::scannedServices($siteId);
        $hadScan = (bool) Customsetting::get('cookies_last_scan_at', $siteId);

        foreach ($found as $name => $url) {
            $match = ServiceCatalog::serviceForCookie($name);

            if ($match) {
                $services[] = $match[0];

                continue;
            }

            $unknown[] = $name;

            $cookie = Cookie::query()->forSite($siteId)->where('name', $name)->where('type', 'cookie')->first()
                ?? Cookie::query()->forSite($siteId)->where('type', 'cookie')->get()->first(fn (Cookie $c) => $c->isPattern() && $c->matches($name));

            if (! $cookie) {
                $newCookies[] = $name;
                $cookie = new Cookie([
                    'site_id' => $siteId,
                    'name' => $name,
                    'type' => 'cookie',
                    'source' => 'scan',
                    'category' => Cookie::UNCLASSIFIED,
                    'provider' => $host,
                ]);
            }

            $cookie->last_seen_at = now();
            $cookie->save();
        }

        $services = array_values(array_unique($services));
        $newServices = array_values(array_diff($services, $previousServices, ServiceCatalog::detectedFor($siteId)));

        Customsetting::set('cookies_scan_services', json_encode($services), $siteId);
        Customsetting::set('cookies_last_scan_at', now()->toIso8601String(), $siteId);

        $sync = $this->synchronizer->sync($siteId);

        Cookie::query()
            ->forSite($siteId)
            ->where('type', 'cookie')
            ->get()
            ->filter(fn (Cookie $cookie) => collect(array_keys($found))->contains(fn ($name) => $cookie->matches($name)))
            ->each(fn (Cookie $cookie) => $cookie->forceFill(['last_seen_at' => now()])->saveQuietly());

        // Alleen bijgewerkte "laatst gezien"-stempels: daarvoor hoeft geen
        // pagina uit de cache.

        $result = [
            'pages' => count($visited),
            'services' => $services,
            'cookies' => array_keys($found),
            'unknown' => $unknown,
            'errors' => array_slice($errors, 0, 5),
            'created' => $sync['created'],
            'removed' => $sync['removed'],
        ];

        Customsetting::set('cookies_last_scan_result', json_encode($result), $siteId);

        // De eerste scan vindt per definitie alles: daarover geen melding.
        if ($hadScan) {
            app(NewCookieNotifier::class)->notify($siteId, $newServices, $newCookies);
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    protected function internalLinks(string $html, string $base, string $host): array
    {
        preg_match_all('/<a\s[^>]*href\s*=\s*["\']([^"\'#]+)["\']/i', $html, $matches);

        $links = [];

        foreach ($matches[1] ?? [] as $href) {
            $href = html_entity_decode(trim($href));

            if (str_starts_with($href, '//')) {
                $href = 'https:'.$href;
            } elseif (str_starts_with($href, '/')) {
                $href = $base.$href;
            }

            $parts = parse_url($href);

            if (! is_array($parts) || ($parts['host'] ?? null) !== $host || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
                continue;
            }

            $path = $parts['path'] ?? '/';

            // Geen bestanden, geen account- of afrekenpagina's die zonder
            // sessie toch alleen doorsturen.
            if (preg_match('/\.(pdf|jpe?g|png|gif|webp|svg|zip|xml|txt|css|js)$/i', $path)) {
                continue;
            }

            $links[] = $base.$path;
        }

        return array_values(array_unique($links));
    }
}
