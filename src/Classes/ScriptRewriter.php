<?php

namespace Dashed\DashedCookies\Classes;

/**
 * Zet elk uitvoerbaar <script> in losse HTML op type="text/plain" met
 * data-dashed-consent, zodat de lader het pas uitvoert na toestemming.
 *
 * Een beheerder bepaalt de categorie per script met
 * data-cookie-category="analytics" (of necessary, dan blijft het script
 * gewoon draaien). Zonder dat attribuut geldt de standaardcategorie uit de
 * instellingen. JSON-LD en andere niet-uitvoerbare types blijven ongemoeid.
 * Een script dat al op type="text/plain" staat omdat het uitgesteld wordt
 * (data-dashed-delayed) krijgt de categorie erbij.
 */
class ScriptRewriter
{
    protected const JAVASCRIPT_TYPES = ['text/javascript', 'application/javascript', 'module', 'text/ecmascript', 'application/ecmascript'];

    public static function rewrite(string $html, string $defaultCategory): string
    {
        if ($html === '' || stripos($html, '<script') === false) {
            return $html;
        }

        return (string) preg_replace_callback('/<script\b([^>]*)>/i', function (array $match) use ($defaultCategory): string {
            $attributes = $match[1];

            if (preg_match('/\sdata-dashed-consent\b/i', $attributes)) {
                return $match[0];
            }

            $category = $defaultCategory;

            if (preg_match('/\sdata-cookie-category\s*=\s*["\']?([a-z_ ]+)["\']?/i', $attributes, $chosen)) {
                $category = strtolower(trim($chosen[1]));
            }

            if ($category === 'necessary') {
                return $match[0];
            }

            $delayed = (bool) preg_match('/\sdata-dashed-delayed\b/i', $attributes);

            if (preg_match('/\stype\s*=\s*["\']?([^"\'\s>]+)["\']?/i', $attributes, $type)) {
                $isJavascript = in_array(strtolower($type[1]), self::JAVASCRIPT_TYPES, true);

                if (! $isJavascript && ! ($delayed && strtolower($type[1]) === 'text/plain')) {
                    return $match[0];
                }

                $attributes = (string) preg_replace('/\stype\s*=\s*["\']?[^"\'\s>]+["\']?/i', '', $attributes, 1);
            }

            return '<script type="text/plain" data-dashed-consent="'.e($category).'"'.$attributes.'>';
        }, $html);
    }
}
