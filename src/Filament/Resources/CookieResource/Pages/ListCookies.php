<?php

namespace Dashed\DashedCookies\Filament\Resources\CookieResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Dashed\DashedCore\Classes\Sites;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCookies\Jobs\ScanCookiesJob;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use Dashed\DashedCookies\Services\CookieSynchronizer;
use Dashed\DashedCookies\Filament\Resources\CookieResource;
use Dashed\DashedCookies\Filament\Pages\Settings\CookieSettingsPage;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListCookies extends ListRecords
{
    use Translatable;

    protected static string $resource = CookieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scan')
                ->label(__('Site scannen'))
                ->icon('heroicon-o-magnifying-glass')
                ->requiresConfirmation()
                ->modalDescription(__('De scan haalt de homepage en tot tien pagina\'s daarvandaan op, leest welke cookies de server zet en herkent bekende scripts zoals Google Analytics, Meta, Hotjar en TikTok. Hij draait op de achtergrond; ververs deze lijst over een minuut.'))
                ->visible(fn () => CookieResource::canCreate())
                ->action(function () {
                    foreach (Sites::getSites() as $site) {
                        ScanCookiesJob::dispatch((string) $site['id']);
                    }

                    Notification::make()
                        ->title(__('De scan is gestart'))
                        ->body(__('Nieuwe cookies verschijnen hier zodra hij klaar is. Cookies zonder categorie moet je zelf indelen.'))
                        ->success()
                        ->send();
                }),
            Action::make('sync')
                ->label(__('Bijwerken'))
                ->icon('heroicon-o-arrow-path')
                ->tooltip(__('Zet de cookies van de diensten die nu aan staan (zoals een ingevuld Google Analytics-ID) in de lijst.'))
                ->visible(fn () => CookieResource::canCreate())
                ->action(function () {
                    $synchronizer = app(CookieSynchronizer::class);

                    foreach (Sites::getSites() as $site) {
                        $synchronizer->sync((string) $site['id']);
                    }

                    Notification::make()->title(__('Het cookieoverzicht is bijgewerkt'))->success()->send();
                }),
            Action::make('settings')
                ->label(__('Instellingen'))
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->url(fn () => CookieSettingsPage::getUrl()),
            LocaleSwitcher::make(),
            CreateAction::make(),
        ];
    }
}
