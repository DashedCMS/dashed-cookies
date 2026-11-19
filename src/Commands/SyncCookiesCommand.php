<?php

namespace Dashed\DashedCookies\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCookies\Services\CookieSynchronizer;

class SyncCookiesCommand extends Command
{
    protected $signature = 'dashed:cookies:sync {--site= : Alleen deze site}';

    protected $description = 'Zet de cookies van de actieve diensten in het cookieoverzicht';

    public function handle(CookieSynchronizer $synchronizer): int
    {
        foreach (Sites::getSites() as $site) {
            $siteId = (string) $site['id'];

            if ($this->option('site') && $this->option('site') !== $siteId) {
                continue;
            }

            $result = $synchronizer->sync($siteId);
            $this->info("{$siteId}: ".count($result['services'])." diensten, {$result['created']} nieuw, {$result['removed']} weg.");
        }

        return self::SUCCESS;
    }
}
