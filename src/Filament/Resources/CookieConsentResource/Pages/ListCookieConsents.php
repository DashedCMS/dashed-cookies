<?php

namespace Dashed\DashedCookies\Filament\Resources\CookieConsentResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCookies\Filament\Resources\CookieConsentResource;

class ListCookieConsents extends ListRecords
{
    protected static string $resource = CookieConsentResource::class;

    protected function getHeaderWidgets(): array
    {
        return CookieConsentResource::getWidgets();
    }
}
