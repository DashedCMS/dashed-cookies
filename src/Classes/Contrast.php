<?php

namespace Dashed\DashedCookies\Classes;

/**
 * WCAG-contrast tussen twee kleuren. AA vraagt 4,5:1 voor gewone tekst.
 */
class Contrast
{
    public const AA = 4.5;

    public static function ratio(string $foreground, string $background): ?float
    {
        $a = static::luminance($foreground);
        $b = static::luminance($background);

        if ($a === null || $b === null) {
            return null;
        }

        return round((max($a, $b) + 0.05) / (min($a, $b) + 0.05), 2);
    }

    /**
     * @return array<int, string> waarschuwingen, leeg als alles leesbaar is
     */
    public static function warnings(array $colors): array
    {
        $pairs = [
            [__('Tekst op de knoppen'), $colors['primary_text'] ?? '#ffffff', $colors['primary'] ?? '#111827'],
            [__('Tekst in de banner'), $colors['text'] ?? '#1f2937', $colors['background'] ?? '#ffffff'],
        ];

        $warnings = [];

        foreach ($pairs as [$label, $foreground, $background]) {
            $ratio = static::ratio((string) $foreground, (string) $background);

            if ($ratio !== null && $ratio < self::AA) {
                $warnings[] = __(':label is slecht leesbaar: contrast :ratio:1, minimaal 4,5:1 (WCAG AA).', [
                    'label' => $label,
                    'ratio' => number_format($ratio, 1, ',', ''),
                ]);
            }
        }

        return $warnings;
    }

    protected static function luminance(string $hex): ?float
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) === 8) {
            $hex = substr($hex, 0, 6);
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }

        $channels = array_map(function (string $pair) {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }
}
