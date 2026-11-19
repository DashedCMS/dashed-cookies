<?php

namespace Dashed\DashedCookies\Classes;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Dashed\DashedCookies\Models\Cookie;

/**
 * Wat het blok Cookieoverzicht toont: de zichtbare, ingedeelde cookies van
 * de site per categorie, in de vaste volgorde noodzakelijk, voorkeuren,
 * statistieken, marketing. Een categorie zonder cookies valt weg.
 */
class CookieOverview
{
    protected const LABELS = [
        'nl' => ['name' => 'Naam', 'provider' => 'Aanbieder', 'purpose' => 'Doel', 'duration' => 'Bewaartermijn', 'type' => 'Soort', 'updated' => 'Laatst bijgewerkt'],
        'en' => ['name' => 'Name', 'provider' => 'Provider', 'purpose' => 'Purpose', 'duration' => 'Retention', 'type' => 'Type', 'updated' => 'Last updated'],
        'de' => ['name' => 'Name', 'provider' => 'Anbieter', 'purpose' => 'Zweck', 'duration' => 'Speicherdauer', 'type' => 'Art', 'updated' => 'Zuletzt aktualisiert'],
    ];

    protected const TYPES = [
        'nl' => ['cookie' => 'Cookie', 'local_storage' => 'Lokale opslag', 'session_storage' => 'Sessieopslag'],
        'en' => ['cookie' => 'Cookie', 'local_storage' => 'Local storage', 'session_storage' => 'Session storage'],
        'de' => ['cookie' => 'Cookie', 'local_storage' => 'Lokaler Speicher', 'session_storage' => 'Sitzungsspeicher'],
    ];

    /**
     * @return Collection<string, Collection<int, Cookie>>
     */
    public static function grouped(string $siteId): Collection
    {
        $cookies = Cookie::query()
            ->forSite($siteId)
            ->public()
            ->orderBy('provider')
            ->orderBy('name')
            ->get();

        return collect(Cookie::CATEGORIES)
            ->mapWithKeys(fn (string $category) => [$category => $cookies->where('category', $category)->values()])
            ->filter(fn (Collection $group) => $group->isNotEmpty());
    }

    public static function lastUpdated(string $siteId): ?CarbonInterface
    {
        $value = Cookie::query()->forSite($siteId)->public()->max('updated_at');

        return $value ? \Illuminate\Support\Carbon::parse($value) : null;
    }

    public static function label(string $key, ?string $locale = null): string
    {
        $locale = substr((string) ($locale ?: app()->getLocale()), 0, 2);

        return self::LABELS[$locale][$key] ?? self::LABELS['en'][$key] ?? $key;
    }

    public static function typeLabels(?string $locale = null): array
    {
        $locale = substr((string) ($locale ?: app()->getLocale()), 0, 2);

        return self::TYPES[$locale] ?? self::TYPES['en'];
    }
}
