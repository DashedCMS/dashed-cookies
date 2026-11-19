<?php

namespace Dashed\DashedCookies;

use Filament\Panel;
use Filament\Contracts\Plugin;
use Dashed\DashedCookies\Filament\Resources\CookieResource;
use Dashed\DashedCookies\Filament\Pages\Settings\CookieSettingsPage;
use Dashed\DashedCookies\Filament\Resources\CookieConsentResource;

class DashedCookiesPlugin implements Plugin
{
    public function getId(): string
    {
        return 'dashed-cookies';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([
                CookieResource::class,
                CookieConsentResource::class,
            ])
            ->pages([
                CookieSettingsPage::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
    }
}
