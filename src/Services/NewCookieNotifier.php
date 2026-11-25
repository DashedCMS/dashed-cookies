<?php

namespace Dashed\DashedCookies\Services;

use Dashed\DashedCore\Classes\Mails;
use Illuminate\Support\Facades\Mail;
use Dashed\DashedCore\Classes\Sites;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Dashed\DashedCookies\Classes\CookieSettings;
use Dashed\DashedCookies\Filament\Resources\CookieResource;
use Dashed\DashedCookies\Filament\Pages\Settings\CookieSettingsPage;

/**
 * Meldt de beheerders dat de scan iets nieuws vond: een dienst die er vorige
 * keer niet was, of een onbekende cookie. Een nieuwe dienst die cookies
 * plaatst is ook een reden om opnieuw toestemming te vragen; dat beslist de
 * beheerder, niet de scan.
 *
 * Alleen als de banner aan staat: zonder banner is er geen toestemming om
 * opnieuw te vragen, en zou elke eerste scan meteen mailen.
 */
class NewCookieNotifier
{
    /**
     * @param  array<int, string>  $newServices  servicesleutels
     * @param  array<int, string>  $newCookies  namen van onbekende cookies
     */
    public function notify(string $siteId, array $newServices, array $newCookies): void
    {
        if (! CookieSettings::enabled($siteId) || (! $newServices && ! $newCookies)) {
            return;
        }

        $catalog = ServiceCatalog::all();
        $lines = [];

        if ($newServices) {
            $lines[] = __('Nieuwe diensten: :services.', ['services' => collect($newServices)->map(fn ($key) => $catalog[$key]['name'] ?? $key)->unique()->implode(', ')]);
        }

        if ($newCookies) {
            $lines[] = __('Onbekende cookies: :cookies. Deel ze in bij Cookies.', ['cookies' => implode(', ', $newCookies)]);
        }

        if ($newServices) {
            $lines[] = __('Plaatst een nieuwe dienst cookies, vraag dan opnieuw toestemming bij Instellingen, Cookies.');
        }

        $siteName = collect(Sites::getSites())->firstWhere('id', $siteId)['name'] ?? $siteId;
        $title = __('Nieuwe cookies gevonden op :site', ['site' => $siteName]);
        $body = implode(' ', $lines);

        // Het model uit de auth-config: het belletje leest meldingen op de
        // morph-klasse van de ingelogde gebruiker, en dat is in klantprojecten
        // App\Models\User en niet het basismodel uit core.
        $userModel = config('auth.providers.users.model') ?: \Dashed\DashedCore\Models\User::class;

        foreach ($userModel::query()->panelUsers()->get() as $admin) {
            rescue(fn () => Notification::make()
                ->title($title)
                ->body($body)
                ->warning()
                ->actions([
                    Action::make('cookies')->label(__('Cookies bekijken'))->url(CookieResource::getUrl()),
                    Action::make('settings')->label(__('Instellingen'))->url(CookieSettingsPage::getUrl()),
                ])
                ->sendToDatabase($admin), report: false);
        }

        foreach (Mails::getAdminNotificationEmails() as $email) {
            rescue(fn () => Mail::to($email)->send(new \Dashed\DashedCore\Mail\NotificationMail($body, $title)), report: false);
        }
    }
}
