<?php

namespace Dashed\DashedCookies\Seo;

use Dashed\DashedSeo\Checks\Finding;
use Dashed\DashedSeo\Contracts\SeoCheck;
use Dashed\DashedSeo\Models\SiteAudit;
use Dashed\DashedCookies\Classes\CookieHealth;

/**
 * Sitescan: meet- of marketingdiensten zonder cookiebanner. Alleen
 * aangemeld als dashed-seo er is.
 */
class CookieBannerMissingCheck implements SeoCheck
{
    public function key(): string
    {
        return 'cookie_banner_missing';
    }

    public function section(): string
    {
        return 'content';
    }

    public function severity(): string
    {
        return 'error';
    }

    public function label(): string
    {
        return __('Tracking zonder cookietoestemming');
    }

    public function description(): string
    {
        return __('De site gebruikt meet- of marketingdiensten, maar vraagt geen toestemming. Dat mag niet onder de AVG en de Telecommunicatiewet.');
    }

    public function howToFix(): string
    {
        return __('Zet de cookiebanner aan bij Instellingen, Cookies, en controleer het cookieoverzicht.');
    }

    public function run(SiteAudit $audit): iterable
    {
        foreach (CookieHealth::issues((string) $audit->site_id) as $issue) {
            if ($issue['key'] === 'cookie_banner' && $issue['status'] === CookieHealth::DANGER) {
                yield new Finding($this->key(), $this->section(), $this->severity(), null, null, ['detail' => $issue['detail']]);
            }
        }
    }
}
