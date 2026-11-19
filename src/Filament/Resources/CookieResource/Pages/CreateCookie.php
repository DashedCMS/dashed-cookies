<?php

namespace Dashed\DashedCookies\Filament\Resources\CookieResource\Pages;

use Dashed\DashedCore\Classes\Sites;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Dashed\DashedCookies\Filament\Resources\CookieResource;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateCookie extends CreateRecord
{
    use Translatable;

    protected static string $resource = CookieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['site_id'] = $data['site_id'] ?? (string) (Sites::getFirstSite()['id'] ?? Sites::getActive());
        $data['source'] = 'manual';

        return $data;
    }
}
