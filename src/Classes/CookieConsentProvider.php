<?php

namespace Dashed\DashedCookies\Classes;

use Dashed\DashedCookies\Models\Cookie;
use Dashed\DashedCore\Classes\Consent\ConsentProvider;

class CookieConsentProvider implements ConsentProvider
{
    public function active(): bool
    {
        return CookieSettings::enabled();
    }

    public function headStart(): string
    {
        return view('dashed-cookies::head', [
            'config' => $this->config(),
        ])->render();
    }

    public function bodyEnd(): string
    {
        $siteId = CookieSettings::siteId();

        return view('dashed-cookies::banner', [
            'config' => $this->config(),
            'layout' => CookieSettings::layout($siteId),
            'position' => CookieSettings::position($siteId),
            'colors' => CookieSettings::colors($siteId),
            'radius' => CookieSettings::radius($siteId),
            'categories' => CookieSettings::categories($siteId),
            'floatingButton' => CookieSettings::floatingButton($siteId),
            'logo' => CookieSettings::showLogo($siteId) ? \Dashed\DashedCore\Models\Customsetting::get('site_logo', $siteId) : null,
            'policyUrl' => CookieSettings::policyUrl($siteId),
            'text' => fn (string $key) => CookieSettings::text($key, $siteId),
            'cookieGroups' => $this->cookieGroups($siteId),
            'typeLabels' => CookieOverview::typeLabels(),
        ])->render();
    }

    public function loadsGoogleWithoutConsent(): bool
    {
        return CookieSettings::consentMode() === 'advanced';
    }

    public function rewrite(string $html): string
    {
        return ScriptRewriter::rewrite($html, CookieSettings::extraScriptsCategory());
    }

    public function allows(string $category): bool
    {
        return ConsentCookie::allows($category);
    }

    /**
     * Wat de lader in de browser nodig heeft. Alleen sitebrede gegevens,
     * nooit iets per bezoeker, zodat de pagina in de responscache kan.
     */
    public function config(): array
    {
        $siteId = CookieSettings::siteId();

        return [
            'cookie' => ConsentCookie::NAME,
            'version' => CookieSettings::version($siteId),
            'lifetimeDays' => CookieSettings::lifetimeDays($siteId),
            'categories' => array_values(array_merge(['necessary'], CookieSettings::categories($siteId))),
            'endpoint' => route('dashed.cookies.consent'),
            'respectGpc' => CookieSettings::respectGpc($siteId),
            // Namen van cookies per categorie, om bij intrekken op te ruimen.
            'cookieNames' => $this->cookieNamesPerCategory($siteId),
        ];
    }

    protected function cookieGroups(string $siteId): \Illuminate\Support\Collection
    {
        try {
            return CookieOverview::grouped($siteId);
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function cookieNamesPerCategory(string $siteId): array
    {
        try {
            return Cookie::query()
                ->forSite($siteId)
                ->whereIn('category', CookieSettings::OPTIONAL_CATEGORIES)
                ->where('type', 'cookie')
                ->get(['name', 'category'])
                ->groupBy('category')
                ->map(fn ($rows) => $rows->pluck('name')->unique()->values()->all())
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
