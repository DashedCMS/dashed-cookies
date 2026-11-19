<?php

namespace Dashed\DashedCookies\Filament\Resources\CookieResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Dashed\DashedCookies\Filament\Resources\CookieResource;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditCookie extends EditRecord
{
    use Translatable;

    protected static string $resource = CookieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LocaleSwitcher::make(),
            // Een automatische regel komt bij de volgende synchronisatie
            // gewoon terug; verbergen is dan de juiste knop.
            DeleteAction::make()->visible(fn () => $this->record->source !== 'builtin'),
        ];
    }

    /**
     * Wie een automatische of gescande regel aanpast, wil niet dat de
     * synchronisatie hem terugzet.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->source !== 'manual') {
            $data['customized'] = true;
        }

        return $data;
    }
}
