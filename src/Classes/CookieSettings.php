<?php

namespace Dashed\DashedCookies\Classes;

use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;

/**
 * Alle instellingen van het toestemmingsbeheer per site, met hun
 * standaardwaarden op één plek. De teksten zijn per taal; zonder ingevulde
 * tekst valt de banner terug op TEXTS hieronder.
 */
class CookieSettings
{
    public const LAYOUTS = ['box', 'bar', 'modal'];

    public const CONSENT_MODES = ['basic', 'advanced'];

    public const OPTIONAL_CATEGORIES = ['preferences', 'analytics', 'marketing'];

    public const TEXT_KEYS = [
        'title', 'text', 'accept', 'reject', 'customize', 'save', 'policy_link', 'settings_title', 'settings_text', 'floating_label',
        'necessary_label', 'necessary_description',
        'preferences_label', 'preferences_description',
        'analytics_label', 'analytics_description',
        'marketing_label', 'marketing_description',
        'consent_id_label', 'always_on',
        'embed_text', 'embed_button', 'gpc_notice', 'show_cookies',
    ];

    public const TEXTS = [
        'nl' => [
            'title' => 'Wij gebruiken cookies',
            'text' => 'We gebruiken noodzakelijke cookies om de website te laten werken. Met jouw toestemming gebruiken we ook cookies om het gebruik te meten en om advertenties af te stemmen. Je kunt je keuze altijd weer aanpassen.',
            'accept' => 'Alles accepteren',
            'reject' => 'Alles weigeren',
            'customize' => 'Instellingen',
            'save' => 'Keuze opslaan',
            'policy_link' => 'Meer over cookies',
            'settings_title' => 'Cookie-instellingen',
            'settings_text' => 'Kies per soort welke cookies je toestaat. Noodzakelijke cookies staan altijd aan, zonder die werkt de website niet.',
            'floating_label' => 'Cookie-instellingen',
            'necessary_label' => 'Noodzakelijk',
            'necessary_description' => 'Nodig om de website te laten werken, bijvoorbeeld voor je winkelwagen, inloggen en beveiliging.',
            'preferences_label' => 'Voorkeuren',
            'preferences_description' => 'Onthouden je keuzes, zoals je taal, zodat de website beter op je aansluit.',
            'analytics_label' => 'Statistieken',
            'analytics_description' => 'Helpen ons begrijpen hoe bezoekers de website gebruiken, zodat we hem kunnen verbeteren.',
            'marketing_label' => 'Marketing',
            'marketing_description' => 'Worden gebruikt om advertenties af te stemmen op je interesses en het resultaat ervan te meten, ook op andere websites.',
            'consent_id_label' => 'Jouw toestemmings-ID',
            'always_on' => 'Altijd aan',
            'embed_text' => 'Deze inhoud komt van :provider: en kan cookies plaatsen.',
            'embed_button' => 'Toch laden',
            'gpc_notice' => 'Je browser vraagt om niet gevolgd te worden. Marketingcookies staan daarom uit, tenzij je ze zelf aanzet.',
            'show_cookies' => 'Bekijk cookies',
        ],
        'en' => [
            'title' => 'We use cookies',
            'text' => 'We use necessary cookies to make this website work. With your consent we also use cookies to measure usage and to tailor advertising. You can change your choice at any time.',
            'accept' => 'Accept all',
            'reject' => 'Reject all',
            'customize' => 'Settings',
            'save' => 'Save choice',
            'policy_link' => 'More about cookies',
            'settings_title' => 'Cookie settings',
            'settings_text' => 'Choose which types of cookies you allow. Necessary cookies are always on, the website does not work without them.',
            'floating_label' => 'Cookie settings',
            'necessary_label' => 'Necessary',
            'necessary_description' => 'Needed to make the website work, for example for your cart, logging in and security.',
            'preferences_label' => 'Preferences',
            'preferences_description' => 'Remember your choices, such as your language, so the website suits you better.',
            'analytics_label' => 'Statistics',
            'analytics_description' => 'Help us understand how visitors use the website, so we can improve it.',
            'marketing_label' => 'Marketing',
            'marketing_description' => 'Used to tailor ads to your interests and measure their results, also on other websites.',
            'consent_id_label' => 'Your consent ID',
            'always_on' => 'Always on',
            'embed_text' => 'This content comes from :provider: and may set cookies.',
            'embed_button' => 'Load anyway',
            'gpc_notice' => 'Your browser asks not to be tracked. Marketing cookies are therefore off, unless you turn them on yourself.',
            'show_cookies' => 'View cookies',
        ],
        'de' => [
            'title' => 'Wir verwenden Cookies',
            'text' => 'Wir verwenden notwendige Cookies, damit die Website funktioniert. Mit Ihrer Einwilligung verwenden wir auch Cookies, um die Nutzung zu messen und Werbung anzupassen. Sie können Ihre Wahl jederzeit ändern.',
            'accept' => 'Alle akzeptieren',
            'reject' => 'Alle ablehnen',
            'customize' => 'Einstellungen',
            'save' => 'Auswahl speichern',
            'policy_link' => 'Mehr über Cookies',
            'settings_title' => 'Cookie-Einstellungen',
            'settings_text' => 'Wählen Sie, welche Arten von Cookies Sie zulassen. Notwendige Cookies sind immer aktiv, ohne sie funktioniert die Website nicht.',
            'floating_label' => 'Cookie-Einstellungen',
            'necessary_label' => 'Notwendig',
            'necessary_description' => 'Erforderlich, damit die Website funktioniert, zum Beispiel für den Warenkorb, die Anmeldung und die Sicherheit.',
            'preferences_label' => 'Präferenzen',
            'preferences_description' => 'Speichern Ihre Auswahl, etwa Ihre Sprache, damit die Website besser zu Ihnen passt.',
            'analytics_label' => 'Statistiken',
            'analytics_description' => 'Helfen uns zu verstehen, wie Besucher die Website nutzen, damit wir sie verbessern können.',
            'marketing_label' => 'Marketing',
            'marketing_description' => 'Werden verwendet, um Werbung an Ihre Interessen anzupassen und deren Erfolg zu messen, auch auf anderen Websites.',
            'consent_id_label' => 'Ihre Einwilligungs-ID',
            'always_on' => 'Immer aktiv',
            'embed_text' => 'Dieser Inhalt stammt von :provider: und kann Cookies setzen.',
            'embed_button' => 'Trotzdem laden',
            'gpc_notice' => 'Ihr Browser bittet darum, nicht verfolgt zu werden. Marketing-Cookies sind daher deaktiviert, es sei denn, Sie aktivieren sie selbst.',
            'show_cookies' => 'Cookies anzeigen',
        ],
    ];

    public static function siteId(?string $siteId = null): string
    {
        return (string) ($siteId ?: Sites::getActive());
    }

    public static function enabled(?string $siteId = null): bool
    {
        return (bool) Customsetting::get('cookies_enabled', static::siteId($siteId), false);
    }

    public static function layout(?string $siteId = null): string
    {
        $layout = (string) Customsetting::get('cookies_layout', static::siteId($siteId), 'box');

        return in_array($layout, self::LAYOUTS, true) ? $layout : 'box';
    }

    public static function position(?string $siteId = null): string
    {
        return Customsetting::get('cookies_position', static::siteId($siteId), 'left') === 'right' ? 'right' : 'left';
    }

    public static function consentMode(?string $siteId = null): string
    {
        $mode = (string) Customsetting::get('cookies_consent_mode', static::siteId($siteId), 'basic');

        return in_array($mode, self::CONSENT_MODES, true) ? $mode : 'basic';
    }

    /**
     * De optionele categorieën die op deze site gevraagd worden.
     *
     * @return array<int, string>
     */
    public static function categories(?string $siteId = null): array
    {
        $value = Customsetting::get('cookies_categories', static::siteId($siteId), null);

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            $value = ['analytics', 'marketing'];
        }

        return array_values(array_intersect(self::OPTIONAL_CATEGORIES, $value));
    }

    public static function extraScriptsCategory(?string $siteId = null): string
    {
        $category = (string) Customsetting::get('cookies_extra_scripts_category', static::siteId($siteId), 'marketing');

        return in_array($category, ['necessary', ...self::OPTIONAL_CATEGORIES], true) ? $category : 'marketing';
    }

    public static function version(?string $siteId = null): int
    {
        return max(1, (int) Customsetting::get('cookies_version', static::siteId($siteId), 1));
    }

    /**
     * Opnieuw toestemming vragen: elke eerder gegeven keuze vervalt.
     */
    public static function bumpVersion(?string $siteId = null): int
    {
        $siteId = static::siteId($siteId);
        $version = static::version($siteId) + 1;
        Customsetting::set('cookies_version', $version, $siteId);

        return $version;
    }

    public static function lifetimeDays(?string $siteId = null): int
    {
        $months = (int) Customsetting::get('cookies_lifetime_months', static::siteId($siteId), 12);

        return max(1, min(13, $months)) * 30;
    }

    public static function respectGpc(?string $siteId = null): bool
    {
        return (bool) Customsetting::get('cookies_respect_gpc', static::siteId($siteId), true);
    }

    public static function blockEmbeds(?string $siteId = null): bool
    {
        return (bool) Customsetting::get('cookies_block_embeds', static::siteId($siteId), true);
    }

    public static function floatingButton(?string $siteId = null): bool
    {
        return (bool) Customsetting::get('cookies_floating_button', static::siteId($siteId), true);
    }

    public static function showLogo(?string $siteId = null): bool
    {
        return (bool) Customsetting::get('cookies_show_logo', static::siteId($siteId), false);
    }

    public static function colors(?string $siteId = null): array
    {
        $siteId = static::siteId($siteId);
        $primary = Customsetting::get('cookies_primary_color', $siteId)
            ?: Customsetting::get('mail_primary_color', $siteId)
            ?: '#111827';

        return [
            'primary' => static::color($primary, '#111827'),
            'primary_text' => static::color(Customsetting::get('cookies_primary_text_color', $siteId), '#ffffff'),
            'background' => static::color(Customsetting::get('cookies_background_color', $siteId), '#ffffff'),
            'text' => static::color(Customsetting::get('cookies_text_color', $siteId), '#1f2937'),
        ];
    }

    public static function radius(?string $siteId = null): int
    {
        return max(0, min(32, (int) Customsetting::get('cookies_radius', static::siteId($siteId), 12)));
    }

    /**
     * De pagina met het cookieoverzicht: een gekozen pagina, anders een URL.
     */
    public static function policyUrl(?string $siteId = null, ?string $locale = null): ?string
    {
        $siteId = static::siteId($siteId);
        $pageId = Customsetting::get('cookies_policy_page_id', $siteId);

        if ($pageId && class_exists(\Dashed\DashedPages\Models\Page::class)) {
            try {
                $page = \Dashed\DashedPages\Models\Page::find($pageId);

                if ($page) {
                    return (string) $page->getUrl($locale ?: app()->getLocale());
                }
            } catch (\Throwable) {
            }
        }

        $url = Customsetting::get('cookies_policy_url', $siteId);

        return filled($url) ? (string) $url : null;
    }

    public static function text(string $key, ?string $siteId = null, ?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $value = Customsetting::get('cookies_text_'.$key, static::siteId($siteId), null, $locale);

        if (filled($value)) {
            return (string) $value;
        }

        return static::defaultText($key, $locale);
    }

    public static function defaultText(string $key, ?string $locale = null): string
    {
        $locale = substr((string) ($locale ?: app()->getLocale()), 0, 2);

        return self::TEXTS[$locale][$key] ?? self::TEXTS['en'][$key] ?? $key;
    }

    protected static function color(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        return preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) ? $value : $fallback;
    }
}
