<?php

namespace Dashed\DashedCookies\Classes;

use Dashed\DashedCookies\Models\Cookie;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCookies\Services\ServiceCatalog;
use Dashed\DashedCookies\Services\CookieSynchronizer;

/**
 * Wat er per site mis is aan het toestemmingsbeheer. De Beveiligingscheck,
 * de Sitescan en de melding na een cookiescan lezen allemaal hier, zodat ze
 * hetzelfde zeggen.
 */
class CookieHealth
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const DANGER = 'danger';

    public const INFO = 'info';

    /** Diensten die zelf een cookiebanner zijn. */
    public const OTHER_BANNERS = ['cookiebot', 'cookieyes', 'spatie_cookie_consent'];

    /** Diensten die zonder toestemming niet mogen draaien. */
    protected const TRACKING_CATEGORIES = ['analytics', 'marketing'];

    /**
     * @return array<int, array{key: string, label: string, status: string, detail: string}>
     */
    public static function issues(string $siteId): array
    {
        $enabled = CookieSettings::enabled($siteId);
        $tracking = static::trackingServices($siteId);
        $otherBanners = array_values(array_intersect(self::OTHER_BANNERS, CookieSynchronizer::scannedServices($siteId)));

        $items = [];

        if (! $enabled) {
            $items[] = [
                'key' => 'cookie_banner',
                'label' => __('Cookiebanner'),
                'status' => $tracking && ! $otherBanners ? self::DANGER : self::INFO,
                'detail' => $tracking
                    ? ($otherBanners
                        ? __('Uit, maar de site gebruikt al :banner. Overstappen kan bij Instellingen, Cookies.', ['banner' => static::names($otherBanners)])
                        : __('Uit, terwijl :services zonder toestemming meten. Zet de cookiebanner aan bij Instellingen, Cookies.', ['services' => static::names($tracking)]))
                    : __('Uit. De site gebruikt geen bekende meet- of marketingdiensten.'),
            ];

            return $items;
        }

        $items[] = [
            'key' => 'cookie_banner',
            'label' => __('Cookiebanner'),
            'status' => self::OK,
            'detail' => __('Aan. Meet- en marketingscripts wachten op toestemming.'),
        ];

        if ($otherBanners) {
            $items[] = [
                'key' => 'cookie_other_banner',
                'label' => __('Tweede cookiebanner'),
                'status' => self::WARNING,
                'detail' => __('De scan vond ook :banner op de site. Bezoekers krijgen dan twee banners; haal de oude weg (php artisan dashed:cookies:migrate-banner toont waar).', ['banner' => static::names($otherBanners)]),
            ];
        }

        if (! CookieSettings::policyUrl($siteId)) {
            $items[] = [
                'key' => 'cookie_policy_page',
                'label' => __('Cookiepagina'),
                'status' => self::WARNING,
                'detail' => __('Geen cookiepagina gekozen. Zet het blok Cookieoverzicht op een pagina en kies die bij Instellingen, Cookies.'),
            ];
        }

        $unclassified = Cookie::query()->forSite($siteId)->where('category', Cookie::UNCLASSIFIED)->where('visible', true)->count();

        if ($unclassified > 0) {
            $items[] = [
                'key' => 'cookie_unclassified',
                'label' => __('Niet ingedeelde cookies'),
                'status' => self::WARNING,
                'detail' => $unclassified === 1
                    ? __('De scan vond 1 cookie zonder categorie. Die staat niet op de cookiepagina en wordt niet tegengehouden.')
                    : __('De scan vond :count cookies zonder categorie. Die staan niet op de cookiepagina en worden niet tegengehouden.', ['count' => $unclassified]),
            ];
        }

        $lastScan = Customsetting::get('cookies_last_scan_at', $siteId);

        if (! $lastScan || now()->diffInDays(\Illuminate\Support\Carbon::parse($lastScan)) > 14) {
            $items[] = [
                'key' => 'cookie_scan',
                'label' => __('Cookiescan'),
                'status' => self::INFO,
                'detail' => $lastScan
                    ? __('De laatste scan is van :date. Draait de scheduler op de server?', ['date' => \Illuminate\Support\Carbon::parse($lastScan)->format('d-m-Y')])
                    : __('Nog nooit gescand. Start een scan bij Cookies.'),
            ];
        }

        return $items;
    }

    /**
     * Actieve diensten in een meet- of marketingcategorie.
     *
     * @return array<int, string>
     */
    public static function trackingServices(string $siteId): array
    {
        $catalog = ServiceCatalog::all();
        $active = array_unique(array_merge(ServiceCatalog::detectedFor($siteId), CookieSynchronizer::scannedServices($siteId)));

        return array_values(array_filter($active, fn (string $key) => in_array($catalog[$key]['category'] ?? null, self::TRACKING_CATEGORIES, true)));
    }

    protected static function names(array $keys): string
    {
        $catalog = ServiceCatalog::all();

        return collect($keys)->map(fn ($key) => $catalog[$key]['name'] ?? $key)->unique()->implode(', ');
    }
}
