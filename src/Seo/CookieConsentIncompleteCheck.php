<?php

namespace Dashed\DashedCookies\Seo;

use Dashed\DashedSeo\Checks\Finding;
use Dashed\DashedSeo\Contracts\SeoCheck;
use Dashed\DashedSeo\Models\SiteAudit;
use Dashed\DashedCookies\Classes\CookieHealth;

/**
 * Sitescan: de banner staat aan, maar er ontbreekt iets (cookiepagina, niet
 * ingedeelde cookies, een tweede banner). Eén bevinding per site met alle
 * gaten in de context, want de vergelijker werkt op controle en adres.
 */
class CookieConsentIncompleteCheck implements SeoCheck
{
    public function key(): string
    {
        return 'cookie_consent_incomplete';
    }

    public function section(): string
    {
        return 'content';
    }

    public function severity(): string
    {
        return 'warning';
    }

    public function label(): string
    {
        return __('Cookietoestemming niet compleet');
    }

    public function description(): string
    {
        return __('De cookiebanner staat aan, maar het overzicht of de instellingen zijn niet af.');
    }

    public function howToFix(): string
    {
        return __('Zie de details van de bevinding; alles is op te lossen bij Cookies en Instellingen, Cookies.');
    }

    public function run(SiteAudit $audit): iterable
    {
        $gaps = collect(CookieHealth::issues((string) $audit->site_id))
            ->filter(fn (array $issue) => $issue['key'] !== 'cookie_banner' && $issue['status'] === CookieHealth::WARNING)
            ->pluck('detail')
            ->values()
            ->all();

        if ($gaps) {
            yield new Finding($this->key(), $this->section(), $this->severity(), null, null, ['gaps' => $gaps]);
        }
    }
}
