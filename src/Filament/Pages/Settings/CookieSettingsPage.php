<?php

namespace Dashed\DashedCookies\Filament\Pages\Settings;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Dashed\DashedCore\Classes\Sites;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Dashed\DashedCore\Classes\Locales;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Contracts\HasSchemas;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCookies\Jobs\ScanCookiesJob;
use Dashed\DashedCookies\Classes\CookieSettings;
use Dashed\DashedCore\Traits\HasSettingsPermission;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Dashed\DashedCookies\Services\CookieSynchronizer;
use Dashed\DashedCookies\Filament\Resources\CookieResource;

class CookieSettingsPage extends Page implements HasSchemas
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesPageLabels;
    use HasSettingsPermission;
    use InteractsWithSchemas;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Cookies';

    protected string $view = 'dashed-core::settings.pages.default-settings';

    public array $data = [];

    /** Instellingen per site, met hun standaardwaarde. */
    protected const SITE_SETTINGS = [
        'cookies_enabled' => false,
        'cookies_consent_mode' => 'basic',
        'cookies_extra_scripts_category' => 'marketing',
        'cookies_policy_page_id' => null,
        'cookies_policy_url' => null,
        'cookies_lifetime_months' => 12,
        'cookies_floating_button' => true,
        'cookies_respect_gpc' => true,
        'cookies_block_embeds' => true,
        'cookies_layout' => 'box',
        'cookies_position' => 'left',
        'cookies_primary_color' => null,
        'cookies_primary_text_color' => '#ffffff',
        'cookies_background_color' => '#ffffff',
        'cookies_text_color' => '#1f2937',
        'cookies_radius' => 12,
        'cookies_show_logo' => false,
    ];

    public function mount(): void
    {
        $formData = [];

        foreach (Sites::getSites() as $site) {
            $siteId = $site['id'];

            foreach (self::SITE_SETTINGS as $name => $default) {
                $formData["{$name}_{$siteId}"] = Customsetting::get($name, $siteId, $default);
            }

            $formData["cookies_categories_{$siteId}"] = CookieSettings::categories($siteId);

            foreach (Locales::getLocalesForSite($siteId) as $locale) {
                foreach (CookieSettings::TEXT_KEYS as $key) {
                    $formData["cookies_text_{$key}_{$siteId}_{$locale['id']}"] = Customsetting::get('cookies_text_'.$key, $siteId, null, $locale['id']);
                }
            }
        }

        $this->form->fill($formData);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cookies')
                ->label(__('Cookieoverzicht'))
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(fn () => CookieResource::getUrl()),
            Action::make('reconsent')
                ->label(__('Opnieuw toestemming vragen'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription(__('Elke bezoeker krijgt de banner opnieuw te zien en moet opnieuw kiezen. Doe dit als je een nieuwe dienst toevoegt die cookies plaatst, of als het doel van bestaande cookies verandert. Eerder gegeven toestemming geldt daarna niet meer.'))
                ->action(function () {
                    foreach (Sites::getSites() as $site) {
                        CookieSettings::bumpVersion($site['id']);
                    }

                    Notification::make()->title(__('Bezoekers krijgen de banner opnieuw te zien'))->success()->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $tabs = [];

        foreach (Sites::getSites() as $site) {
            $siteId = $site['id'];

            $tabs[] = Tab::make($siteId)
                ->label(ucfirst($site['name']))
                ->schema([
                    Section::make(__('Cookiebanner'))
                        ->key("cookies_section_banner_{$siteId}")
                        ->description(__('Met de banner aan laden Google Analytics, Google Tag Manager, de Meta-pixel en de extra scripts pas als de bezoeker daarvoor toestemming geeft. Elke keuze wordt als bewijs vastgelegd.'))
                        ->schema([
                            Toggle::make("cookies_enabled_{$siteId}")
                                ->label(__('Cookiebanner aan'))
                                ->helperText(__('Zet je dit aan, controleer dan eerst het cookieoverzicht en de teksten hieronder.'))
                                ->columnSpanFull(),
                            CheckboxList::make("cookies_categories_{$siteId}")
                                ->label(__('Te vragen categorieën'))
                                ->helperText(__('Noodzakelijke cookies staan altijd aan. Vraag alleen categorieën die de site ook echt gebruikt.'))
                                ->options([
                                    'preferences' => __('Voorkeuren'),
                                    'analytics' => __('Statistieken'),
                                    'marketing' => __('Marketing'),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),
                            Select::make("cookies_consent_mode_{$siteId}")
                                ->label(__('Google Consent Mode'))
                                ->options([
                                    'basic' => __('Basis: Google laadt pas na toestemming'),
                                    'advanced' => __('Geavanceerd: Google laadt altijd, zonder cookies tot toestemming'),
                                ])
                                ->helperText(__('Basis is de veilige keuze. Bij geavanceerd stuurt Google zonder toestemming cookieloze metingen, zodat Google Ads en GA4 de ontbrekende conversies kunnen schatten. Bespreek dat met de klant: de Autoriteit Persoonsgegevens is daar strenger in dan Google.'))
                                ->required(),
                            Select::make("cookies_extra_scripts_category_{$siteId}")
                                ->label(__('Categorie van extra scripts'))
                                ->options([
                                    'necessary' => __('Noodzakelijk (laden altijd)'),
                                    'preferences' => __('Voorkeuren'),
                                    'analytics' => __('Statistieken'),
                                    'marketing' => __('Marketing'),
                                ])
                                ->helperText(__('Geldt voor de extra scripts bij Instellingen, Algemeen en voor scripts per pagina. Per script kun je afwijken met data-cookie-category="analytics" in de script-tag.'))
                                ->required(),
                            Select::make("cookies_policy_page_id_{$siteId}")
                                ->label(__('Cookiepagina'))
                                ->options(fn () => $this->pageOptions())
                                ->searchable()
                                ->helperText(__('De pagina waar het blok Cookieoverzicht op staat. De banner linkt ernaartoe.'))
                                ->visible(class_exists(\Dashed\DashedPages\Models\Page::class)),
                            TextInput::make("cookies_policy_url_{$siteId}")
                                ->label(__('Of een eigen URL voor de cookiepagina'))
                                ->url()
                                ->maxLength(255),
                            TextInput::make("cookies_lifetime_months_{$siteId}")
                                ->label(__('Keuze onthouden (maanden)'))
                                ->helperText(__('Daarna vraagt de banner het opnieuw. De Autoriteit Persoonsgegevens adviseert hooguit 12 maanden.'))
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(13)
                                ->required(),
                            Toggle::make("cookies_block_embeds_{$siteId}")
                                ->label(__('Video\'s en kaarten tegenhouden'))
                                ->helperText(__('YouTube, Vimeo, Google Maps en andere ingesloten inhoud laadt pas na toestemming. De bezoeker ziet een plaatshouder met een knop om één embed toch te laden. YouTube laadt altijd via youtube-nocookie.com.')),
                            Toggle::make("cookies_respect_gpc_{$siteId}")
                                ->label(__('Global Privacy Control respecteren'))
                                ->helperText(__('Vraagt de browser om niet gevolgd te worden (GPC), dan zet "Alles accepteren" marketing niet aan en staat er een korte melding in de banner. De bezoeker kan marketing in de instellingen nog wel zelf aanzetten.')),
                            Toggle::make("cookies_floating_button_{$siteId}")
                                ->label(__('Zwevende cookieknop'))
                                ->helperText(__('Een kleine knop in de hoek waarmee een bezoeker zijn keuze altijd kan aanpassen. Zet je hem uit, zet dan een link in de footer met data-cookie-settings of <x-dashed-cookies::settings-link/>.')),
                        ])
                        ->columns(2),

                    Section::make(__('Vormgeving'))
                        ->key("cookies_section_design_{$siteId}")
                        ->schema([
                            Select::make("cookies_layout_{$siteId}")
                                ->live()
                                ->label(__('Weergave'))
                                ->options([
                                    'box' => __('Blok in de hoek'),
                                    'bar' => __('Balk onderin'),
                                    'modal' => __('Venster in het midden'),
                                ])
                                ->required(),
                            Select::make("cookies_position_{$siteId}")
                                ->live()
                                ->label(__('Hoek'))
                                ->options([
                                    'left' => __('Links'),
                                    'right' => __('Rechts'),
                                ])
                                ->helperText(__('Voor het blok en de zwevende knop.'))
                                ->required(),
                            ColorPicker::make("cookies_primary_color_{$siteId}")
                                ->live()
                                ->label(__('Knopkleur'))
                                ->helperText(__('Leeg is de hoofdkleur van de e-mails.')),
                            ColorPicker::make("cookies_primary_text_color_{$siteId}")
                                ->live()
                                ->label(__('Tekstkleur op knoppen')),
                            ColorPicker::make("cookies_background_color_{$siteId}")
                                ->live()
                                ->label(__('Achtergrond')),
                            ColorPicker::make("cookies_text_color_{$siteId}")
                                ->live()
                                ->label(__('Tekstkleur')),
                            TextInput::make("cookies_radius_{$siteId}")
                                ->live(onBlur: true)
                                ->label(__('Afronding (px)'))
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(32),
                            Toggle::make("cookies_show_logo_{$siteId}")
                                ->label(__('Logo tonen')),
                            \Filament\Schemas\Components\View::make('dashed-cookies::filament.preview')
                                ->viewData(fn (\Filament\Schemas\Components\Utilities\Get $get) => $this->previewData($get, (string) $siteId))
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->collapsible(),

                    Section::make(__('Teksten'))
                        ->key("cookies_section_texts_{$siteId}")
                        ->description(__('Leeg laten gebruikt de standaardtekst, die als voorbeeld in elk veld staat.'))
                        ->schema([
                            Tabs::make("cookies_texts_{$siteId}")
                                ->tabs(collect(Locales::getLocalesForSite($siteId))
                                    ->map(fn (array $locale) => Tab::make("cookies_texts_{$siteId}_{$locale['id']}")
                                        ->label($locale['native'] ?? $locale['id'])
                                        ->schema($this->textFields($siteId, $locale['id']))
                                        ->columns(2))
                                    ->all()),
                        ])
                        ->collapsible()
                        ->collapsed(),
                ]);
        }

        return $schema->schema([
            Tabs::make('Sites')->tabs($tabs),
        ])->statePath('data');
    }

    protected function textFields(string $siteId, string $locale): array
    {
        $labels = [
            'title' => __('Titel'),
            'text' => __('Tekst'),
            'accept' => __('Knop accepteren'),
            'reject' => __('Knop weigeren'),
            'customize' => __('Knop instellingen'),
            'save' => __('Knop keuze opslaan'),
            'policy_link' => __('Linktekst naar de cookiepagina'),
            'settings_title' => __('Titel instellingen'),
            'settings_text' => __('Tekst instellingen'),
            'floating_label' => __('Label zwevende knop'),
            'necessary_label' => __('Noodzakelijk: naam'),
            'necessary_description' => __('Noodzakelijk: uitleg'),
            'preferences_label' => __('Voorkeuren: naam'),
            'preferences_description' => __('Voorkeuren: uitleg'),
            'analytics_label' => __('Statistieken: naam'),
            'analytics_description' => __('Statistieken: uitleg'),
            'marketing_label' => __('Marketing: naam'),
            'marketing_description' => __('Marketing: uitleg'),
            'consent_id_label' => __('Label toestemmings-ID'),
            'always_on' => __('Tekst "altijd aan"'),
            'embed_text' => __('Tekst bij een tegengehouden video of kaart (:provider: wordt de dienst)'),
            'embed_button' => __('Knop video of kaart laden'),
            'gpc_notice' => __('Melding bij Global Privacy Control'),
            'show_cookies' => __('Linktekst cookies per categorie'),
        ];

        $long = ['text', 'settings_text', 'gpc_notice', 'embed_text', 'necessary_description', 'preferences_description', 'analytics_description', 'marketing_description'];

        return collect(CookieSettings::TEXT_KEYS)
            ->map(function (string $key) use ($siteId, $locale, $labels, $long) {
                $name = "cookies_text_{$key}_{$siteId}_{$locale}";
                $field = in_array($key, $long, true)
                    ? Textarea::make($name)->rows(3)->columnSpanFull()
                    : TextInput::make($name)->maxLength(255);

                if (in_array($key, ['title', 'text', 'accept', 'reject', 'customize', 'policy_link'], true)) {
                    $field->live(onBlur: true);
                }

                return $field
                    ->label($labels[$key] ?? $key)
                    ->placeholder(CookieSettings::defaultText($key, $locale));
            })
            ->all();
    }

    /**
     * Wat het voorbeeld toont: de vormgeving uit het formulier, en de teksten
     * in de eerste taal van de site (leeg = standaardtekst).
     */
    protected function previewData(\Filament\Schemas\Components\Utilities\Get $get, string $siteId): array
    {
        $locale = Locales::getLocalesForSite($siteId)[0]['id'] ?? app()->getLocale();
        $value = fn (string $name) => $get("cookies_{$name}_{$siteId}");
        $text = fn (string $key) => filled($get("cookies_text_{$key}_{$siteId}_{$locale}"))
            ? (string) $get("cookies_text_{$key}_{$siteId}_{$locale}")
            : CookieSettings::defaultText($key, $locale);
        $color = fn ($v, string $fallback) => preg_match('/^#[0-9a-fA-F]{3,8}$/', trim((string) $v)) ? trim((string) $v) : $fallback;

        $colors = [
            'primary' => $color($value('primary_color') ?: Customsetting::get('mail_primary_color', $siteId), '#111827'),
            'primary_text' => $color($value('primary_text_color'), '#ffffff'),
            'background' => $color($value('background_color'), '#ffffff'),
            'text' => $color($value('text_color'), '#1f2937'),
        ];

        return [
            'layout' => in_array($value('layout'), CookieSettings::LAYOUTS, true) ? $value('layout') : 'box',
            'position' => $value('position') === 'right' ? 'right' : 'left',
            'radius' => max(0, min(32, (int) ($value('radius') ?? 12))),
            'colors' => $colors,
            'warnings' => \Dashed\DashedCookies\Classes\Contrast::warnings($colors),
            'title' => $text('title'),
            'text' => $text('text'),
            'policy' => $text('policy_link'),
            'accept' => $text('accept'),
            'reject' => $text('reject'),
            'customize' => $text('customize'),
        ];
    }

    protected function pageOptions(): array
    {
        if (! class_exists(\Dashed\DashedPages\Models\Page::class)) {
            return [];
        }

        return \Dashed\DashedPages\Models\Page::query()
            ->get()
            ->mapWithKeys(fn ($page) => [$page->id => (string) $page->name])
            ->all();
    }

    public function submit()
    {
        $state = $this->form->getState();

        foreach (Sites::getSites() as $site) {
            $siteId = $site['id'];

            foreach (array_keys(self::SITE_SETTINGS) as $name) {
                Customsetting::set($name, $state["{$name}_{$siteId}"] ?? null, $siteId);
            }

            Customsetting::set('cookies_categories', json_encode(array_values((array) ($state["cookies_categories_{$siteId}"] ?? []))), $siteId);

            foreach (Locales::getLocalesForSite($siteId) as $locale) {
                foreach (CookieSettings::TEXT_KEYS as $key) {
                    Customsetting::set('cookies_text_'.$key, $state["cookies_text_{$key}_{$siteId}_{$locale['id']}"] ?? null, $siteId, $locale['id']);
                }
            }

            // Een ingevuld meet-id of een nieuwe categorie: het overzicht volgt meteen.
            app(CookieSynchronizer::class)->sync((string) $siteId);

            // De eerste keer aanzetten zonder scan: dan meteen een scan.
            if (($state["cookies_enabled_{$siteId}"] ?? false) && ! Customsetting::get('cookies_last_scan_at', $siteId)) {
                ScanCookiesJob::dispatch((string) $siteId);
            }
        }

        Notification::make()
            ->title(__('De cookie-instellingen zijn opgeslagen'))
            ->success()
            ->send();

        foreach (Sites::getSites() as $site) {
            if ($warnings = \Dashed\DashedCookies\Classes\Contrast::warnings(CookieSettings::colors($site['id']))) {
                Notification::make()
                    ->title(__('Let op de leesbaarheid van de banner'))
                    ->body(implode(' ', $warnings))
                    ->warning()
                    ->persistent()
                    ->send();

                break;
            }
        }

        return redirect(static::getUrl());
    }
}
