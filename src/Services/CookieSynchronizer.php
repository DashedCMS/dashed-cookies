<?php

namespace Dashed\DashedCookies\Services;

use Dashed\DashedCookies\Models\Cookie;
use Dashed\DashedCore\Models\Customsetting;

/**
 * Zet de cookies van elke actieve dienst in dashed__cookies voor een site.
 *
 * Actief is een dienst als zijn eigen detectie dat zegt (een ingevuld meet-id,
 * een geïnstalleerd pakket) of als de laatste scan hem op de site vond.
 * Regels die een beheerder heeft aangepast (customized) of zelf heeft
 * toegevoegd (manual) worden nooit overschreven of weggehaald; een
 * automatische regel van een dienst die niet meer actief is wel.
 */
class CookieSynchronizer
{
    public function sync(string $siteId): array
    {
        return \Dashed\DashedCookies\Classes\CookieCache::defer(fn () => $this->run($siteId));
    }

    protected function run(string $siteId): array
    {
        $services = array_values(array_unique(array_merge(
            ServiceCatalog::detectedFor($siteId),
            static::scannedServices($siteId),
        )));

        $catalog = ServiceCatalog::all();
        $kept = [];
        $created = 0;

        foreach ($services as $key) {
            $service = $catalog[$key] ?? null;

            if (! $service) {
                continue;
            }

            foreach ($service['cookies'] as $definition) {
                $type = $definition['type'] ?? 'cookie';
                $name = (string) $definition['name'];

                if ($name === '') {
                    continue;
                }

                $existing = Cookie::query()
                    ->forSite($siteId)
                    ->where('name', $name)
                    ->where('type', $type)
                    ->first();

                $kept[] = $type.'|'.$name;

                if ($existing && ($existing->customized || $existing->source === 'manual')) {
                    continue;
                }

                $cookie = $existing ?? new Cookie(['site_id' => $siteId, 'name' => $name, 'type' => $type]);
                $wasNew = ! $cookie->exists;

                $cookie->fill([
                    // Ook een cookie die de scan eerst als onbekend vond en nu
                    // bij een dienst blijkt te horen, wordt een gewone regel.
                    'source' => 'builtin',
                    'service_key' => $key,
                    'provider' => $service['name'],
                    'category' => $definition['category'] ?? $service['category'],
                    'privacy_url' => $service['privacy_url'],
                ]);

                $cookie->setTranslations('purpose', (array) ($definition['purpose'] ?? []));
                $cookie->setTranslations('duration', (array) ($definition['duration'] ?? []));
                $cookie->save();

                if ($wasNew) {
                    $created++;
                }
            }
        }

        $removed = 0;

        Cookie::query()
            ->forSite($siteId)
            ->where('source', 'builtin')
            ->where('customized', false)
            ->get()
            ->reject(fn (Cookie $cookie) => in_array($cookie->type.'|'.$cookie->name, $kept, true))
            ->each(function (Cookie $cookie) use (&$removed) {
                $cookie->delete();
                $removed++;
            });

        return ['services' => $services, 'created' => $created, 'removed' => $removed];
    }

    /**
     * @return array<int, string>
     */
    public static function scannedServices(string $siteId): array
    {
        $value = Customsetting::get('cookies_scan_services', $siteId, null);

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
    }
}
