<?php

namespace Dashed\DashedCookies\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eén regel in het cookieoverzicht van een site. Komt uit de catalogus
 * (source builtin), uit de scan (scan) of is met de hand toegevoegd
 * (manual). Zodra een beheerder een builtin- of scanregel aanpast staat
 * customized aan en laat de synchronisatie hem met rust.
 */
class Cookie extends Model
{
    use HasTranslations;

    public const CATEGORIES = ['necessary', 'preferences', 'analytics', 'marketing'];

    public const UNCLASSIFIED = 'unclassified';

    public const TYPES = ['cookie', 'local_storage', 'session_storage'];

    protected $table = 'dashed__cookies';

    protected $guarded = [];

    public $translatable = ['purpose', 'duration'];

    protected $casts = [
        'customized' => 'boolean',
        'visible' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Eloquent vuurt saved ook zonder wijziging; de nachtelijke
        // synchronisatie schrijft elke regel opnieuw weg.
        static::saved(function (Cookie $cookie) {
            $changed = array_diff(array_keys($cookie->getChanges()), ['last_seen_at', 'updated_at']);

            if (! $cookie->wasRecentlyCreated && $changed === []) {
                return;
            }

            // Een niet-ingedeelde cookie staat op geen enkele pagina.
            if ($cookie->category === self::UNCLASSIFIED && ! $cookie->wasChanged('category')) {
                return;
            }

            \Dashed\DashedCookies\Classes\CookieCache::touch($cookie->site_id);
        });
        static::deleted(fn (Cookie $cookie) => \Dashed\DashedCookies\Classes\CookieCache::touch($cookie->site_id));
    }

    public static function categoryLabels(): array
    {
        return [
            'necessary' => __('Noodzakelijk'),
            'preferences' => __('Voorkeuren'),
            'analytics' => __('Statistieken'),
            'marketing' => __('Marketing'),
            self::UNCLASSIFIED => __('Niet ingedeeld'),
        ];
    }

    public static function typeLabels(): array
    {
        return [
            'cookie' => __('Cookie'),
            'local_storage' => __('Local storage'),
            'session_storage' => __('Session storage'),
        ];
    }

    public static function sourceLabels(): array
    {
        return [
            'builtin' => __('Automatisch'),
            'scan' => __('Gevonden met scan'),
            'manual' => __('Handmatig'),
        ];
    }

    public function scopeForSite(Builder $query, string $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Wat op de site in het overzicht staat: zichtbaar en ingedeeld.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visible', true)->where('category', '!=', self::UNCLASSIFIED);
    }

    public function isPattern(): bool
    {
        return str_contains($this->name, '*');
    }

    /**
     * Past een echte cookienaam op deze regel? _ga_ABC123 past op _ga_*.
     */
    public function matches(string $cookieName): bool
    {
        return static::nameMatches($this->name, $cookieName);
    }

    public static function nameMatches(string $pattern, string $cookieName): bool
    {
        if (! str_contains($pattern, '*')) {
            return $pattern === $cookieName;
        }

        $regex = '/^'.str_replace('\*', '.*', preg_quote($pattern, '/')).'$/';

        return (bool) preg_match($regex, $cookieName);
    }
}
