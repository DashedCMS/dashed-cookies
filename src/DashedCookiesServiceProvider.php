<?php

namespace Dashed\DashedCookies;

use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Gate;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\TextInput;
use Spatie\LaravelPackageTools\Package;
use Dashed\DashedCore\Retention\Termijn;
use Dashed\DashedCore\Classes\ScheduleSpread;
use Dashed\DashedCore\Retention\Retention;
use Illuminate\Console\Scheduling\Schedule;
use Dashed\DashedCookies\Models\Cookie;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Dashed\DashedCookies\Classes\ConsentCookie;
use Dashed\DashedCookies\Models\CookieConsent;
use Dashed\DashedCookies\Policies\CookiePolicy;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Dashed\DashedCookies\Commands\SyncCookiesCommand;
use Dashed\DashedCookies\Commands\ScanCookiesCommand;
use Dashed\DashedCookies\Classes\CookieConsentProvider;
use Dashed\DashedCookies\Policies\CookieConsentPolicy;
use Dashed\DashedCore\Classes\Consent\ConsentProvider;
use Dashed\DashedCookies\Filament\Resources\CookieResource;
use Dashed\DashedCookies\Filament\Pages\Settings\CookieSettingsPage;

class DashedCookiesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'dashed-cookies';

    public function configurePackage(Package $package): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/frontend.php');
        $this->mergeConfigFrom(__DIR__.'/../config/dashed-cookies.php', 'dashed-cookies');

        // In de register-fase, zoals dashed-popups: het paneel leest de
        // plugins bij het registreren van AppPanelProvider.
        cms()->builder('plugins', [
            new DashedCookiesPlugin(),
        ]);

        // Ook in de register-fase: de front-endroutes lezen deze lijst bij
        // het laden van de routes.
        cms()->builder('frontendMiddlewares', [
            \Dashed\DashedCookies\Middleware\BlockEmbeds::class,
        ]);

        $package->name('dashed-cookies')
            ->hasTranslations()
            ->hasCommands([
                ScanCookiesCommand::class,
                SyncCookiesCommand::class,
                \Dashed\DashedCookies\Commands\MigrateBannerCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        // dashed-core vraagt via ConsentGate of er toestemmingsbeheer is; zonder
        // deze binding laadt alles zoals voorheen.
        $this->app->singleton(ConsentProvider::class, CookieConsentProvider::class);
    }

    public function bootingPackage(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dashed-cookies');

        // Het blok Cookieoverzicht moet in elk project bestaan zonder dat het
        // de view publiceert. Achteraan toegevoegd, dus een eigen
        // resources/views/components/blocks/cookie-overview.blade.php wint.
        $this->app['view']->getFinder()->addLocation(__DIR__.'/../resources/block-views');

        // De banner schrijft dit cookie met JavaScript, onversleuteld.
        if (method_exists(EncryptCookies::class, 'except')) {
            EncryptCookies::except([ConsentCookie::NAME]);
        }

        Gate::policy(Cookie::class, CookiePolicy::class);
        Gate::policy(CookieConsent::class, CookieConsentPolicy::class);

        cms()->builder('builderBlockClasses', [
            self::class => 'builderBlocks',
        ]);

        // Het overzicht verandert met elke scan; de pagina zelf leegt de
        // responscache al als er iets verandert (CookieCache).
        cms()->builder('blockDisabledForCache', [
            'cookie-overview',
        ]);

        cms()->registerRolePermissions('Cookies', [
            'view_cookie' => __('Cookieoverzicht bekijken'),
            'edit_cookie' => __('Cookieoverzicht bewerken en scannen'),
            'delete_cookie' => __('Cookies verwijderen'),
            'view_cookie_consent' => __('Cookietoestemmingen bekijken'),
        ]);

        cms()->registerSettingsPage(
            CookieSettingsPage::class,
            'Cookies',
            'shield-check',
            __('Cookiebanner, Google Consent Mode, de cookiepagina en de teksten per taal.')
        );

        cms()->registerResourceDocs(
            resource: CookieResource::class,
            title: __('Cookies'),
            intro: __('Het overzicht van alle cookies die de website plaatst. Dit staat op de cookiepagina (het blok Cookieoverzicht) en bepaalt welke cookies worden opgeruimd als een bezoeker zijn toestemming intrekt.'),
            sections: [
                [
                    'heading' => __('Waar komen de cookies vandaan?'),
                    'body' => __('Automatisch: cookies van de website zelf en van diensten die je hebt ingesteld, zoals Google Analytics of de Meta-pixel. Gevonden met scan: de scan haalt een aantal pagina\'s op en herkent bekende scripts zoals Hotjar, TikTok en LinkedIn. Handmatig: wat je zelf toevoegt.'),
                ],
                [
                    'heading' => __('Niet ingedeeld'),
                    'body' => __('Vindt de scan een cookie die hij niet kent, dan staat die als "Niet ingedeeld" in de lijst en nog niet op de cookiepagina. Geef hem een categorie en een omschrijving, of verberg hem als hij niet van jouw site is.'),
                ],
                [
                    'heading' => __('Wat de scan niet ziet'),
                    'body' => __('Cookies die JavaScript zet zonder dat het script van een bekende dienst is, ziet de scan niet. Heb je zelf een script toegevoegd bij de extra scripts, voeg de cookies daarvan dan met de hand toe.'),
                ],
            ],
            tips: [
                __('Een automatische cookie aanpassen kan; hij wordt daarna niet meer overschreven.'),
                __('Na het toevoegen van een nieuwe dienst: vraag bij de cookie-instellingen opnieuw toestemming.'),
            ],
        );

        if (method_exists(cms(), 'registerRetention')) {
            self::registreerBewaartermijnen();
        }

        self::registreerVertaalbaren();
        self::registreerControles();

        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = app(Schedule::class);
            $schedule->command('dashed:cookies:sync')->dailyAt(ScheduleSpread::at('04:00', 'cookies-sync'));
            $schedule->command('dashed:cookies:scan --queue')->weeklyOn(1, ScheduleSpread::at('05:00', 'cookies-scan'));
        });
    }

    /**
     * Statisch, zodat een test het register kan legen en opnieuw vullen. Niet
     * in de register-fase: de labels zijn __()-aanroepen.
     */
    public static function registreerBewaartermijnen(): void
    {
        cms()->registerRetention(
            Retention::make('cookie_consents')
                ->label(__('Cookietoestemmingen'))
                ->pakket('dashed-cookies', __('Cookies'))
                ->tabel('dashed__cookie_consents')
                ->termijn(
                    Termijn::make('cookie_consents', 1095, 'created_at')
                        ->label(__('Cookietoestemmingen bewaren (dagen)'))
                        ->uitleg(__('Het bewijs dat een bezoeker toestemming gaf. Bewaar het minstens zo lang als de toestemming geldt, plus de tijd waarin iemand er een vraag over kan stellen. Standaard: 1095 dagen (3 jaar).'))
                )
        );
    }

    /**
     * Doel en bewaartermijn van een cookie zijn vertaalbaar. Guarded:
     * dashed-cookies kent dashed-translations niet als afhankelijkheid.
     */
    public static function registreerVertaalbaren(): void
    {
        if (! class_exists(\Dashed\DashedTranslations\Classes\Translatables\TranslatableRegistry::class)) {
            return;
        }

        \Dashed\DashedTranslations\Classes\Translatables\TranslatableRegistry::register(
            \Dashed\DashedTranslations\Classes\Translatables\Translatable::make(Cookie::class)
                ->label(__('Cookies'))
                ->group(__('SEO & site'))
                ->urlVia(CookieResource::class)
        );
    }

    /**
     * Rijen op de Beveiligingscheck en controles in de Sitescan, beide uit
     * CookieHealth. Allebei optioneel: oudere core of geen dashed-seo.
     */
    public static function registreerControles(): void
    {
        $security = \Dashed\DashedCore\Security\SecurityCheck::class;

        if (class_exists($security) && method_exists($security, 'extend')) {
            $security::extend(function () use ($security): array {
                $statuses = [
                    \Dashed\DashedCookies\Classes\CookieHealth::OK => $security::OK,
                    \Dashed\DashedCookies\Classes\CookieHealth::WARNING => $security::WARNING,
                    \Dashed\DashedCookies\Classes\CookieHealth::DANGER => $security::DANGER,
                    \Dashed\DashedCookies\Classes\CookieHealth::INFO => $security::INFO,
                ];

                $items = [];

                foreach (\Dashed\DashedCore\Classes\Sites::getSites() as $site) {
                    foreach (\Dashed\DashedCookies\Classes\CookieHealth::issues((string) $site['id']) as $issue) {
                        $items[] = [
                            'key' => $issue['key'].'_'.$site['id'],
                            'label' => count(\Dashed\DashedCore\Classes\Sites::getSites()) > 1 ? $issue['label'].' ('.$site['name'].')' : $issue['label'],
                            'status' => $statuses[$issue['status']] ?? $security::INFO,
                            'detail' => $issue['detail'],
                        ];
                    }
                }

                return $items;
            });
        }

        if (class_exists(\Dashed\DashedSeo\SeoCheckRegistry::class) && app()->bound(\Dashed\DashedSeo\SeoCheckRegistry::class)) {
            $registry = app(\Dashed\DashedSeo\SeoCheckRegistry::class);

            foreach ([\Dashed\DashedCookies\Seo\CookieBannerMissingCheck::class, \Dashed\DashedCookies\Seo\CookieConsentIncompleteCheck::class] as $check) {
                $instance = new $check();

                if (! $registry->check($instance->key())) {
                    $registry->register($instance);
                }
            }
        }
    }

    public static function builderBlocks(): void
    {
        $defaultFields = method_exists(\App\Providers\AppServiceProvider::class, 'getDefaultBlockFields')
            ? [\App\Providers\AppServiceProvider::getDefaultBlockFields()]
            : [];

        cms()->builder('blocks', [
            Block::make('cookie-overview')
                ->label(__('Cookieoverzicht'))
                ->schema(array_merge($defaultFields, [
                    TextInput::make('title')
                        ->label(__('Titel')),
                    cms()->editorField('content', 'Inleiding'),
                    Toggle::make('show_settings_button')
                        ->label(__('Knop "Instellingen" tonen'))
                        ->helperText(__('Opent de cookie-instellingen, zodat een bezoeker zijn keuze kan aanpassen.'))
                        ->default(true),
                ])),
        ]);
    }
}
