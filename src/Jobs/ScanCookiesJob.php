<?php

namespace Dashed\DashedCookies\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Dashed\DashedCookies\Services\CookieScanner;

class ScanCookiesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public string $siteId)
    {
    }

    public function uniqueId(): string
    {
        return 'dashed-cookies-scan-'.$this->siteId;
    }

    public function handle(CookieScanner $scanner): void
    {
        $scanner->scan($this->siteId);
    }
}
