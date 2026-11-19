<?php

namespace Dashed\DashedCookies\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCookies\Jobs\ScanCookiesJob;
use Dashed\DashedCookies\Classes\CookieSettings;
use Dashed\DashedCookies\Services\CookieScanner;

class ScanCookiesCommand extends Command
{
    protected $signature = 'dashed:cookies:scan {--site= : Alleen deze site} {--queue : Via de wachtrij in plaats van meteen} {--all : Ook sites waar de banner uit staat}';

    protected $description = 'Scan de site op cookies en werk het cookieoverzicht bij';

    public function handle(CookieScanner $scanner): int
    {
        foreach (Sites::getSites() as $site) {
            $siteId = (string) $site['id'];

            if ($this->option('site') && $this->option('site') !== $siteId) {
                continue;
            }

            if (! $this->option('all') && ! $this->option('site') && ! CookieSettings::enabled($siteId)) {
                continue;
            }

            if ($this->option('queue')) {
                ScanCookiesJob::dispatch($siteId);
                $this->info("Scan voor {$siteId} staat in de wachtrij.");

                continue;
            }

            $result = $scanner->scan($siteId);
            $this->info(sprintf(
                '%s: %d pagina\'s, diensten: %s, onbekende cookies: %s',
                $siteId,
                $result['pages'],
                implode(', ', $result['services']) ?: '-',
                implode(', ', $result['unknown']) ?: '-',
            ));
        }

        return self::SUCCESS;
    }
}
