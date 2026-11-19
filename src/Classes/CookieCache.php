<?php

namespace Dashed\DashedCookies\Classes;

use Dashed\DashedCore\Classes\Caching\CacheInvalidator;

/**
 * Het cookieoverzicht staat in gecachete pagina's (het blok, en de namen per
 * categorie in de lader). Een gewijzigde regel leegt daarom de responscache
 * van zijn site. Tijdens een scan of synchronisatie veranderen tientallen
 * regels tegelijk; dan wordt er aan het eind één keer per site geleegd.
 */
class CookieCache
{
    protected static int $deferring = 0;

    /** @var array<string, true> */
    protected static array $pending = [];

    public static function touch(?string $siteId): void
    {
        if (! $siteId) {
            return;
        }

        if (static::$deferring > 0) {
            static::$pending[$siteId] = true;

            return;
        }

        static::flush($siteId);
    }

    public static function defer(callable $callback): mixed
    {
        static::$deferring++;

        try {
            return $callback();
        } finally {
            static::$deferring--;

            if (static::$deferring === 0) {
                $sites = array_keys(static::$pending);
                static::$pending = [];

                foreach ($sites as $siteId) {
                    static::flush($siteId);
                }
            }
        }
    }

    protected static function flush(string $siteId): void
    {
        if (! class_exists(CacheInvalidator::class)) {
            return;
        }

        rescue(fn () => CacheInvalidator::flushSite($siteId), report: false);
    }
}
