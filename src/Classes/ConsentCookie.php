<?php

namespace Dashed\DashedCookies\Classes;

use Illuminate\Http\Request;

/**
 * Het toestemmingscookie dat de banner in de browser zet:
 * dashed_consent = URL-gecodeerde JSON {"id": uuid, "v": versie, "c": [categorieën], "t": unix-tijd}.
 *
 * Bewust niet versleuteld (de banner schrijft hem met JavaScript) en dus
 * uitgezonderd van EncryptCookies. Er staat niets in dat de bezoeker niet
 * zelf koos.
 */
class ConsentCookie
{
    public const NAME = 'dashed_consent';

    /**
     * @return array{id: string, v: int, c: array<int, string>, t: int}|null
     */
    public static function read(?Request $request = null): ?array
    {
        $request ??= request();

        $raw = $request->cookies->get(self::NAME);

        // Achter EncryptCookies zonder uitzondering (een klantproject met een
        // eigen middleware) is de waarde na ontsleutelen leeg; de ruwe header
        // heeft hem dan nog.
        if (! is_string($raw) || $raw === '') {
            $raw = $_COOKIE[self::NAME] ?? null;
        }

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $data = json_decode(rawurldecode($raw), true);

        if (! is_array($data) || ! isset($data['c']) || ! is_array($data['c'])) {
            return null;
        }

        return [
            'id' => (string) ($data['id'] ?? ''),
            'v' => (int) ($data['v'] ?? 0),
            'c' => array_values(array_filter($data['c'], 'is_string')),
            't' => (int) ($data['t'] ?? 0),
        ];
    }

    /**
     * Toegestaan volgens het cookie, en alleen als het bij de huidige versie
     * van de banner hoort: na "opnieuw toestemming vragen" telt een oude
     * keuze niet meer.
     */
    public static function allows(string $category, ?string $siteId = null, ?Request $request = null): bool
    {
        if ($category === 'necessary') {
            return true;
        }

        $consent = static::read($request);

        if (! $consent || $consent['v'] !== CookieSettings::version($siteId)) {
            return false;
        }

        return in_array($category, $consent['c'], true);
    }
}
