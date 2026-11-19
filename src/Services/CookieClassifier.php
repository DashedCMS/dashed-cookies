<?php

namespace Dashed\DashedCookies\Services;

use Dashed\DashedCookies\Models\Cookie;

/**
 * Laat de AI een voorstel doen voor een cookie die de scan niet kende. Het
 * voorstel wordt nooit vanzelf opgeslagen: de beheerder ziet het in een
 * formulier en slaat het zelf op (of bevestigt de bulkactie).
 */
class CookieClassifier
{
    public static function available(): bool
    {
        return class_exists(\Dashed\DashedAi\Facades\Ai::class)
            && rescue(fn () => (bool) app(\Dashed\DashedAi\AiManager::class)->default(\Dashed\DashedAi\Enums\AiCapability::Json), false, report: false);
    }

    /**
     * @return array{category: string, provider: ?string, purpose: array<string, string>, duration: array<string, string>}|null
     */
    public function suggest(Cookie $cookie): ?array
    {
        if (! static::available()) {
            return null;
        }

        $prompt = implode("\n", [
            'Je helpt een Nederlandse webwinkel met het cookieoverzicht dat de AVG vereist.',
            'Geef voor deze cookie een voorstel als JSON met precies deze sleutels:',
            '"category": een van "necessary", "preferences", "analytics", "marketing";',
            '"provider": wie de cookie plaatst (bekende dienst, anders de naam van de website);',
            '"purpose_nl" en "purpose_en": één korte zin in gewone taal waarvoor de cookie dient;',
            '"duration_nl" en "duration_en": de gebruikelijke bewaartermijn, bijvoorbeeld "1 jaar" / "1 year" of "Sessie" / "Session".',
            'Kies "necessary" alleen als de site zonder de cookie niet werkt (sessie, beveiliging, winkelwagen, toestemming). Twijfel je tussen statistieken en marketing, kies marketing.',
            '',
            'Cookienaam: '.$cookie->name,
            'Soort: '.$cookie->type,
            'Gezet door domein: '.($cookie->provider ?: 'onbekend'),
            'Website: '.config('app.url'),
        ]);

        $answer = \Dashed\DashedAi\Facades\Ai::json($prompt, [
            'purpose' => 'cookie-classification',
            'skip_tone_of_voice' => true,
        ]);

        if (! is_array($answer) || ! in_array($answer['category'] ?? null, Cookie::CATEGORIES, true)) {
            return null;
        }

        $clean = fn ($value) => trim(strip_tags((string) $value));

        return [
            'category' => $answer['category'],
            'provider' => $clean($answer['provider'] ?? '') ?: null,
            'purpose' => array_filter(['nl' => $clean($answer['purpose_nl'] ?? ''), 'en' => $clean($answer['purpose_en'] ?? '')]),
            'duration' => array_filter(['nl' => $clean($answer['duration_nl'] ?? ''), 'en' => $clean($answer['duration_en'] ?? '')]),
        ];
    }

    /**
     * Zet een voorstel op de cookie, als aangepast zodat de synchronisatie
     * hem niet terugdraait.
     */
    public function apply(Cookie $cookie, array $suggestion): void
    {
        $cookie->category = $suggestion['category'];

        if (! empty($suggestion['provider'])) {
            $cookie->provider = $suggestion['provider'];
        }

        if (! empty($suggestion['purpose'])) {
            $cookie->setTranslations('purpose', $suggestion['purpose']);
        }

        if (! empty($suggestion['duration'])) {
            $cookie->setTranslations('duration', $suggestion['duration']);
        }

        if ($cookie->source !== 'manual') {
            $cookie->customized = true;
        }

        $cookie->save();
    }
}
