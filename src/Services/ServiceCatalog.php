<?php

namespace Dashed\DashedCookies\Services;

use Dashed\DashedCore\Models\Customsetting;

/**
 * Bekende diensten met hun cookies, in het Nederlands en Engels.
 *
 * Een dienst komt op twee manieren in het overzicht van een site: doordat
 * `detect` zegt dat hij aan staat (een ingevuld meet-id in de instellingen,
 * een geïnstalleerd pakket), of doordat de scan een van zijn `signatures` in
 * de HTML van de site vond (een in "extra scripts" geplakte Hotjar-tag
 * bijvoorbeeld).
 *
 * Een ander pakket meldt zijn eigen dienst aan met
 * `cms()->builder('cookieServices', ['sleutel' => [...]])`, in dezelfde vorm
 * als hieronder. Dat pakket heeft dashed-cookies dan niet nodig.
 */
class ServiceCatalog
{
    /**
     * @return array<string, array{name: string, category: string, privacy_url: ?string, cookies: array, signatures?: array<int, string>, detect?: callable}>
     */
    public static function all(): array
    {
        $services = array_merge(static::builtin(), (array) cms()->builder('cookieServices'));

        foreach ($services as $key => $service) {
            $services[$key] = array_merge([
                'name' => $key,
                'category' => 'necessary',
                'privacy_url' => null,
                'cookies' => [],
                'signatures' => [],
                'detect' => null,
            ], $service);
        }

        return $services;
    }

    public static function find(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /**
     * Diensten die volgens hun eigen detectie aan staan op deze site.
     *
     * @return array<int, string>
     */
    public static function detectedFor(string $siteId): array
    {
        $keys = [];

        foreach (static::all() as $key => $service) {
            $detect = $service['detect'];

            if ($detect === null) {
                continue;
            }

            try {
                if ($detect($siteId)) {
                    $keys[] = $key;
                }
            } catch (\Throwable) {
                // Een detectie die faalt (ontbrekende tabel, ontbrekend pakket)
                // telt als niet gevonden.
            }
        }

        return $keys;
    }

    /**
     * Diensten waarvan een handtekening in de HTML staat.
     *
     * @return array<int, string>
     */
    public static function detectInHtml(string $html): array
    {
        $keys = [];

        foreach (static::all() as $key => $service) {
            foreach ($service['signatures'] as $signature) {
                if (stripos($html, $signature) !== false) {
                    $keys[] = $key;

                    break;
                }
            }
        }

        return $keys;
    }

    /**
     * De dienst waar een gevonden cookienaam bij hoort, met de cookiedefinitie.
     *
     * @return array{0: string, 1: array}|null
     */
    public static function serviceForCookie(string $cookieName): ?array
    {
        foreach (static::all() as $key => $service) {
            foreach ($service['cookies'] as $cookie) {
                if (\Dashed\DashedCookies\Models\Cookie::nameMatches($cookie['name'], $cookieName)) {
                    return [$key, $cookie];
                }
            }
        }

        return null;
    }

    protected static function setting(string $name, string $siteId): bool
    {
        return filled(Customsetting::get($name, $siteId));
    }

    protected static function builtin(): array
    {
        $year = ['nl' => '1 jaar', 'en' => '1 year'];
        $session = ['nl' => 'Sessie', 'en' => 'Session'];
        $persistent = ['nl' => 'Blijvend', 'en' => 'Persistent'];
        $months = fn (int $n) => ['nl' => $n.' maanden', 'en' => $n.' months'];
        $days = fn (int $n) => ['nl' => $n.' dagen', 'en' => $n.' days'];

        return [
            'website' => [
                'name' => config('app.name') ?: 'Website',
                'category' => 'necessary',
                'detect' => fn () => true,
                'cookies' => [
                    [
                        'name' => (string) config('session.cookie', 'laravel_session'),
                        'purpose' => ['nl' => 'Houdt je sessie bij, zodat de website onthoudt wat je doet terwijl je hem gebruikt.', 'en' => 'Keeps track of your session so the website remembers what you do while you use it.'],
                        'duration' => ['nl' => (int) config('session.lifetime', 120).' minuten', 'en' => (int) config('session.lifetime', 120).' minutes'],
                    ],
                    [
                        'name' => 'XSRF-TOKEN',
                        'purpose' => ['nl' => 'Beveiligt formulieren tegen misbruik door andere websites.', 'en' => 'Protects forms against abuse by other websites.'],
                        'duration' => ['nl' => (int) config('session.lifetime', 120).' minuten', 'en' => (int) config('session.lifetime', 120).' minutes'],
                    ],
                    [
                        'name' => 'dashed_consent',
                        'purpose' => ['nl' => 'Onthoudt welke cookies je hebt toegestaan.', 'en' => 'Remembers which cookies you allowed.'],
                        'duration' => $year,
                    ],
                    [
                        'name' => 'remember_web_*',
                        'purpose' => ['nl' => 'Houdt je ingelogd als je "onthoud mij" aanvinkt.', 'en' => 'Keeps you logged in when you tick "remember me".'],
                        'duration' => ['nl' => '400 dagen', 'en' => '400 days'],
                    ],
                    [
                        'name' => 'dashed_identified',
                        'purpose' => ['nl' => 'Geeft aan dat je bent ingelogd geweest, zodat je geen opgeslagen versie van een pagina te zien krijgt.', 'en' => 'Indicates you have logged in, so you are not served a cached version of a page.'],
                        'duration' => ['nl' => '400 dagen', 'en' => '400 days'],
                    ],
                ],
            ],

            'webshop' => [
                'name' => config('app.name') ?: 'Webshop',
                'category' => 'necessary',
                'detect' => fn () => class_exists(\Dashed\DashedEcommerceCore\Models\Order::class),
                'cookies' => [
                    [
                        'name' => (string) config('dashed-ecommerce.cart_cookie', 'cart_token'),
                        'purpose' => ['nl' => 'Onthoudt wat er in je winkelwagen zit.', 'en' => 'Remembers what is in your shopping cart.'],
                        'duration' => $days(90),
                    ],
                    [
                        'name' => (string) config('dashed-ecommerce-core.wishlist_cookie', 'wishlist_token'),
                        'purpose' => ['nl' => 'Onthoudt welke producten op je verlanglijst staan.', 'en' => 'Remembers which products are on your wishlist.'],
                        'duration' => $year,
                    ],
                ],
            ],

            'livechat' => [
                'name' => __('Livechat'),
                'category' => 'necessary',
                'detect' => fn () => class_exists(\Dashed\DashedLivechat\Models\ChatConversation::class),
                'cookies' => [
                    [
                        'name' => 'dashed_livechat_token_*',
                        'type' => 'local_storage',
                        'purpose' => ['nl' => 'Houdt je chatgesprek bij, zodat het niet verdwijnt als je naar een andere pagina gaat.', 'en' => 'Keeps your chat conversation, so it does not disappear when you go to another page.'],
                        'duration' => $persistent,
                    ],
                    [
                        'name' => 'dashed_visitor_token',
                        'type' => 'local_storage',
                        'purpose' => ['nl' => 'Een willekeurige code waarmee de chat je herkent als je terugkomt.', 'en' => 'A random code the chat uses to recognise you when you return.'],
                        'duration' => $persistent,
                    ],
                    [
                        'name' => 'dashed_chat_pageviews_*',
                        'type' => 'local_storage',
                        'category' => 'preferences',
                        'purpose' => ['nl' => 'Telt hoeveel pagina\'s je bekeek, zodat de chat zich op het juiste moment aanbiedt.', 'en' => 'Counts how many pages you viewed, so the chat offers itself at the right moment.'],
                        'duration' => $persistent,
                    ],
                ],
            ],

            'newsletter_recognition' => [
                'name' => __('Nieuwsbrief'),
                'category' => 'marketing',
                'detect' => fn () => class_exists(\Dashed\DashedNewsletter\Models\NewsletterList::class)
                    && \Dashed\DashedNewsletter\Models\NewsletterList::query()->where('recognize_visitors', true)->exists(),
                'cookies' => [
                    [
                        'name' => 'dashed_newsletter_visitor',
                        'purpose' => ['nl' => 'Herkent je als je via een link in onze nieuwsbrief op de website komt, zodat we je passende aanbiedingen kunnen tonen.', 'en' => 'Recognises you when you arrive from a link in our newsletter, so we can show you relevant offers.'],
                        'duration' => $days(30),
                    ],
                ],
            ],

            'google_analytics' => [
                'name' => 'Google Analytics',
                'category' => 'analytics',
                'privacy_url' => 'https://policies.google.com/privacy',
                'detect' => fn (string $siteId) => static::setting('google_analytics_id', $siteId) || static::setting('google_tagmanager_id', $siteId),
                'signatures' => ['googletagmanager.com/gtag/js', 'google-analytics.com/analytics.js', "gtag('config', 'G-"],
                'cookies' => [
                    [
                        'name' => '_ga',
                        'purpose' => ['nl' => 'Onderscheidt bezoekers, zodat we kunnen meten hoe de website gebruikt wordt.', 'en' => 'Distinguishes visitors, so we can measure how the website is used.'],
                        'duration' => ['nl' => '2 jaar', 'en' => '2 years'],
                    ],
                    [
                        'name' => '_ga_*',
                        'purpose' => ['nl' => 'Houdt de sessie bij voor Google Analytics 4.', 'en' => 'Keeps the session state for Google Analytics 4.'],
                        'duration' => ['nl' => '2 jaar', 'en' => '2 years'],
                    ],
                ],
            ],

            'google_ads' => [
                'name' => 'Google Ads',
                'category' => 'marketing',
                'privacy_url' => 'https://policies.google.com/privacy',
                'signatures' => ['googleadservices.com', 'googleads.g.doubleclick.net', "'AW-"],
                'cookies' => [
                    [
                        'name' => '_gcl_au',
                        'purpose' => ['nl' => 'Meet welke advertenties tot een aankoop of aanvraag leiden.', 'en' => 'Measures which ads lead to a purchase or enquiry.'],
                        'duration' => $months(3),
                    ],
                    [
                        'name' => 'IDE',
                        'purpose' => ['nl' => 'Wordt door Google DoubleClick gebruikt om advertenties te tonen en te meten.', 'en' => 'Used by Google DoubleClick to show and measure ads.'],
                        'duration' => $year,
                    ],
                ],
            ],

            'meta_pixel' => [
                'name' => 'Meta (Facebook)',
                'category' => 'marketing',
                'privacy_url' => 'https://www.facebook.com/privacy/policy/',
                'detect' => fn (string $siteId) => static::setting('facebook_pixel_conversion_id', $siteId) || static::setting('facebook_pixel_site_id', $siteId),
                'signatures' => ['connect.facebook.net', "fbq('init'"],
                'cookies' => [
                    [
                        'name' => '_fbp',
                        'purpose' => ['nl' => 'Wordt door Meta gebruikt om advertenties te tonen en het resultaat ervan te meten.', 'en' => 'Used by Meta to show ads and measure their results.'],
                        'duration' => $months(3),
                    ],
                    [
                        'name' => '_fbc',
                        'purpose' => ['nl' => 'Onthoudt via welke Facebook- of Instagram-advertentie je binnenkwam.', 'en' => 'Remembers which Facebook or Instagram ad you came from.'],
                        'duration' => $months(3),
                    ],
                ],
            ],

            'hotjar' => [
                'name' => 'Hotjar',
                'category' => 'analytics',
                'privacy_url' => 'https://www.hotjar.com/legal/policies/privacy/',
                'signatures' => ['static.hotjar.com', 'hotjar.com/c/hotjar-'],
                'cookies' => [
                    [
                        'name' => '_hjSessionUser_*',
                        'purpose' => ['nl' => 'Herkent een terugkerende bezoeker voor het meten van gebruik van de website.', 'en' => 'Recognises a returning visitor to measure how the website is used.'],
                        'duration' => $year,
                    ],
                    [
                        'name' => '_hjSession_*',
                        'purpose' => ['nl' => 'Houdt de gegevens van het huidige bezoek bij.', 'en' => 'Holds the data of the current visit.'],
                        'duration' => ['nl' => '30 minuten', 'en' => '30 minutes'],
                    ],
                ],
            ],

            'microsoft_clarity' => [
                'name' => 'Microsoft Clarity',
                'category' => 'analytics',
                'privacy_url' => 'https://privacy.microsoft.com/privacystatement',
                'signatures' => ['clarity.ms/tag'],
                'cookies' => [
                    [
                        'name' => '_clck',
                        'purpose' => ['nl' => 'Herkent een bezoeker voor het meten van gebruik van de website.', 'en' => 'Recognises a visitor to measure how the website is used.'],
                        'duration' => $year,
                    ],
                    [
                        'name' => '_clsk',
                        'purpose' => ['nl' => 'Koppelt de pagina\'s van één bezoek aan elkaar.', 'en' => 'Links the pages of a single visit together.'],
                        'duration' => ['nl' => '1 dag', 'en' => '1 day'],
                    ],
                ],
            ],

            'microsoft_ads' => [
                'name' => 'Microsoft Advertising',
                'category' => 'marketing',
                'privacy_url' => 'https://privacy.microsoft.com/privacystatement',
                'signatures' => ['bat.bing.com'],
                'cookies' => [
                    [
                        'name' => '_uetsid',
                        'purpose' => ['nl' => 'Meet welke Bing-advertenties tot een bezoek leiden.', 'en' => 'Measures which Bing ads lead to a visit.'],
                        'duration' => ['nl' => '1 dag', 'en' => '1 day'],
                    ],
                    [
                        'name' => '_uetvid',
                        'purpose' => ['nl' => 'Herkent een bezoeker over meerdere bezoeken heen voor Bing-advertenties.', 'en' => 'Recognises a visitor across visits for Bing ads.'],
                        'duration' => ['nl' => '13 maanden', 'en' => '13 months'],
                    ],
                ],
            ],

            'tiktok' => [
                'name' => 'TikTok',
                'category' => 'marketing',
                'privacy_url' => 'https://www.tiktok.com/legal/privacy-policy',
                'signatures' => ['analytics.tiktok.com'],
                'cookies' => [
                    [
                        'name' => '_ttp',
                        'purpose' => ['nl' => 'Meet het resultaat van advertenties op TikTok.', 'en' => 'Measures the results of ads on TikTok.'],
                        'duration' => ['nl' => '13 maanden', 'en' => '13 months'],
                    ],
                ],
            ],

            'linkedin' => [
                'name' => 'LinkedIn',
                'category' => 'marketing',
                'privacy_url' => 'https://www.linkedin.com/legal/privacy-policy',
                'signatures' => ['snap.licdn.com', '_linkedin_partner_id'],
                'cookies' => [
                    [
                        'name' => 'li_fat_id',
                        'purpose' => ['nl' => 'Meet welke LinkedIn-advertenties tot een aanvraag leiden.', 'en' => 'Measures which LinkedIn ads lead to an enquiry.'],
                        'duration' => $days(30),
                    ],
                    [
                        'name' => 'li_sugr',
                        'purpose' => ['nl' => 'Wordt door LinkedIn gebruikt om een bezoeker te herkennen.', 'en' => 'Used by LinkedIn to recognise a visitor.'],
                        'duration' => $months(3),
                    ],
                ],
            ],

            'pinterest' => [
                'name' => 'Pinterest',
                'category' => 'marketing',
                'privacy_url' => 'https://policy.pinterest.com/privacy-policy',
                'signatures' => ['s.pinimg.com/ct/core.js'],
                'cookies' => [
                    [
                        'name' => '_pin_unauth',
                        'purpose' => ['nl' => 'Meet het resultaat van advertenties op Pinterest.', 'en' => 'Measures the results of ads on Pinterest.'],
                        'duration' => $year,
                    ],
                ],
            ],

            'hubspot' => [
                'name' => 'HubSpot',
                'category' => 'marketing',
                'privacy_url' => 'https://legal.hubspot.com/privacy-policy',
                'signatures' => ['js.hs-scripts.com', 'js.hs-analytics.net'],
                'cookies' => [
                    [
                        'name' => 'hubspotutk',
                        'purpose' => ['nl' => 'Herkent een bezoeker, zodat formulierinzendingen aan hem gekoppeld worden.', 'en' => 'Recognises a visitor so form submissions are linked to them.'],
                        'duration' => $months(6),
                    ],
                    [
                        'name' => '__hstc',
                        'purpose' => ['nl' => 'Houdt bezoeken bij voor HubSpot.', 'en' => 'Tracks visits for HubSpot.'],
                        'duration' => $months(6),
                    ],
                ],
            ],

            'youtube' => [
                'name' => 'YouTube',
                'category' => 'marketing',
                'privacy_url' => 'https://policies.google.com/privacy',
                'signatures' => ['youtube.com/embed', 'youtube.com/iframe_api'],
                'cookies' => [
                    [
                        'name' => 'YSC',
                        'purpose' => ['nl' => 'Wordt door YouTube gezet bij een ingesloten video om het afspelen bij te houden.', 'en' => 'Set by YouTube on an embedded video to track playback.'],
                        'duration' => $session,
                    ],
                    [
                        'name' => 'VISITOR_INFO1_LIVE',
                        'purpose' => ['nl' => 'Wordt door YouTube gebruikt om je voorkeuren en interesses bij te houden.', 'en' => 'Used by YouTube to track your preferences and interests.'],
                        'duration' => $months(6),
                    ],
                ],
            ],

            'vimeo' => [
                'name' => 'Vimeo',
                'category' => 'analytics',
                'privacy_url' => 'https://vimeo.com/privacy',
                'signatures' => ['player.vimeo.com'],
                'cookies' => [
                    [
                        'name' => 'vuid',
                        'purpose' => ['nl' => 'Wordt door Vimeo gebruikt om het afspelen van een ingesloten video te meten.', 'en' => 'Used by Vimeo to measure playback of an embedded video.'],
                        'duration' => ['nl' => '2 jaar', 'en' => '2 years'],
                    ],
                ],
            ],

            'recaptcha' => [
                'name' => 'Google reCAPTCHA',
                'category' => 'necessary',
                'privacy_url' => 'https://policies.google.com/privacy',
                'signatures' => ['google.com/recaptcha', 'recaptcha/api.js'],
                'cookies' => [
                    [
                        'name' => '_GRECAPTCHA',
                        'purpose' => ['nl' => 'Beschermt formulieren tegen spam en misbruik.', 'en' => 'Protects forms against spam and abuse.'],
                        'duration' => $months(6),
                    ],
                ],
            ],

            'cloudflare' => [
                'name' => 'Cloudflare',
                'category' => 'necessary',
                'privacy_url' => 'https://www.cloudflare.com/privacypolicy/',
                'cookies' => [
                    [
                        'name' => '__cf_bm',
                        'purpose' => ['nl' => 'Onderscheidt mensen van bots, om de website te beschermen.', 'en' => 'Distinguishes people from bots to protect the website.'],
                        'duration' => ['nl' => '30 minuten', 'en' => '30 minutes'],
                    ],
                    [
                        'name' => 'cf_clearance',
                        'purpose' => ['nl' => 'Onthoudt dat je een beveiligingscontrole hebt doorstaan.', 'en' => 'Remembers that you passed a security check.'],
                        'duration' => $year,
                    ],
                ],
            ],

            // Andere cookiebanners: herkend zodat de overstap en een dubbele
            // banner opvallen (CookieHealth, dashed:cookies:migrate-banner).
            'cookiebot' => [
                'name' => 'Cookiebot',
                'category' => 'necessary',
                'privacy_url' => 'https://www.cookiebot.com/en/privacy-policy/',
                'signatures' => ['consent.cookiebot.com', 'consentcdn.cookiebot.com'],
                'cookies' => [
                    [
                        'name' => 'CookieConsent',
                        'purpose' => ['nl' => 'Onthoudt je cookiekeuze in Cookiebot.', 'en' => 'Stores your cookie choice in Cookiebot.'],
                        'duration' => $year,
                    ],
                ],
            ],

            'cookieyes' => [
                'name' => 'CookieYes',
                'category' => 'necessary',
                'privacy_url' => 'https://www.cookieyes.com/privacy-policy/',
                'signatures' => ['cdn-cookieyes.com'],
                'cookies' => [
                    [
                        'name' => 'cookieyes-consent',
                        'purpose' => ['nl' => 'Onthoudt je cookiekeuze in CookieYes.', 'en' => 'Stores your cookie choice in CookieYes.'],
                        'duration' => $year,
                    ],
                ],
            ],

            'spatie_cookie_consent' => [
                'name' => config('app.name') ?: 'Website',
                'category' => 'necessary',
                'signatures' => ['js-cookie-consent'],
                'cookies' => [
                    [
                        'name' => 'laravel_cookie_consent',
                        'purpose' => ['nl' => 'Onthoudt dat je de oude cookiemelding hebt gezien.', 'en' => 'Remembers that you saw the old cookie notice.'],
                        'duration' => ['nl' => '20 jaar', 'en' => '20 years'],
                    ],
                ],
            ],

            'localization' => [
                'name' => config('app.name') ?: 'Website',
                'category' => 'preferences',
                'cookies' => [
                    [
                        'name' => 'locale',
                        'purpose' => ['nl' => 'Onthoudt in welke taal je de website bekijkt.', 'en' => 'Remembers which language you view the website in.'],
                        'duration' => $persistent,
                    ],
                ],
            ],
        ];
    }
}
