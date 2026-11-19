<?php

namespace Dashed\DashedCookies\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Classes\Sites;
use Symfony\Component\Finder\Finder;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCookies\Classes\CookieSettings;

/**
 * Laat per klantproject zien waar een oude cookiebanner zit (Cookiebot,
 * CookieYes, spatie/laravel-cookie-consent), zodat er na het aanzetten van
 * dashed-cookies geen twee banners staan. Wijzigt niets.
 */
class MigrateBannerCommand extends Command
{
    protected $signature = 'dashed:cookies:migrate-banner';

    protected $description = 'Toon waar een oude cookiebanner (Cookiebot, CookieYes, spatie) in dit project zit';

    protected const PATTERNS = [
        'consent.cookiebot.com' => 'Cookiebot-script',
        'consentcdn.cookiebot.com' => 'Cookiebot-script',
        'cookiebot_id' => 'Cookiebot-instelling',
        'data-cookieconsent' => 'Cookiebot-attribuut (data-cookieconsent)',
        'Cookiebot.' => 'Cookiebot-JavaScript-aanroep',
        'cdn-cookieyes.com' => 'CookieYes-script',
        'cookie-consent::index' => 'spatie/laravel-cookie-consent banner',
        'js-cookie-consent' => 'spatie/laravel-cookie-consent banner',
        'laravel_cookie_consent' => 'spatie/laravel-cookie-consent cookie',
    ];

    public function handle(): int
    {
        $rows = [];

        $directories = array_filter([
            resource_path('views'),
            app_path(),
            config_path(),
        ], 'is_dir');

        $finder = (new Finder())->files()->in($directories)->name(['*.php', '*.js'])->ignoreVCS(true);

        foreach ($finder as $file) {
            foreach (preg_split('/\R/', $file->getContents()) as $number => $line) {
                foreach (self::PATTERNS as $needle => $label) {
                    if (str_contains($line, $needle)) {
                        $rows[] = [$label, str_replace(base_path().'/', '', $file->getRealPath()).':'.($number + 1), mb_strimwidth(trim($line), 0, 90, '...')];

                        break;
                    }
                }
            }
        }

        $composer = base_path('composer.json');

        if (is_file($composer) && str_contains((string) file_get_contents($composer), 'spatie/laravel-cookie-consent')) {
            $rows[] = ['spatie/laravel-cookie-consent', 'composer.json', __('Rechtstreeks vereist; kan weg als de banner niet meer gebruikt wordt.')];
        }

        foreach (Sites::getSites() as $site) {
            foreach (['extra_scripts', 'extra_body_scripts', 'cookiebot_id'] as $setting) {
                $value = (string) Customsetting::get($setting, $site['id']);

                foreach (self::PATTERNS as $needle => $label) {
                    if ($value !== '' && ($setting === 'cookiebot_id' || str_contains($value, $needle))) {
                        $rows[] = [$label, __('Instelling :setting (site :site)', ['setting' => $setting, 'site' => $site['id']]), mb_strimwidth($value, 0, 90, '...')];

                        break;
                    }
                }
            }
        }

        if (! $rows) {
            $this->info(__('Geen oude cookiebanner gevonden.'));

            return self::SUCCESS;
        }

        $this->table([__('Wat'), __('Waar'), __('Regel')], $rows);

        $enabled = collect(Sites::getSites())->filter(fn ($site) => CookieSettings::enabled((string) $site['id']))->isNotEmpty();

        $this->newLine();
        $this->line($enabled
            ? __('De nieuwe cookiebanner staat aan: haal bovenstaande weg, anders zien bezoekers twee banners.')
            : __('Zet eerst de nieuwe cookiebanner aan bij Instellingen, Cookies en haal daarna bovenstaande weg. data-cookieconsent-attributen zijn onschuldig maar overbodig.'));

        return self::SUCCESS;
    }
}
