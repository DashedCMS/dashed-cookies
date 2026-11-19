<?php

namespace Dashed\DashedCookies\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Dashed\DashedCore\Classes\Sites;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Dashed\DashedCookies\Models\Cookie;
use Dashed\DashedCookies\Services\CookieClassifier;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Dashed\DashedCookies\Filament\Resources\CookieResource\Pages\EditCookie;
use Dashed\DashedCookies\Filament\Resources\CookieResource\Pages\ListCookies;
use Dashed\DashedCookies\Filament\Resources\CookieResource\Pages\CreateCookie;

class CookieResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;
    use Translatable;

    protected static ?string $model = Cookie::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'SEO & site';

    protected static ?string $navigationLabel = 'Cookies';

    protected static ?string $label = 'Cookie';

    protected static ?string $pluralLabel = 'Cookies';

    protected static ?int $navigationSort = 80;

    protected static bool $isGloballySearchable = false;

    /**
     * Cookies die de scan vond maar die nog niemand heeft ingedeeld: die staan
     * niet op de cookiepagina en worden niet door de banner tegengehouden.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Cookie::query()->where('category', Cookie::UNCLASSIFIED)->where('visible', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Gevonden cookies die nog ingedeeld moeten worden');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('Cookie'))
                ->schema([
                    Select::make('site_id')
                        ->label(__('Site'))
                        ->options(collect(Sites::getSites())->mapWithKeys(fn ($site) => [$site['id'] => $site['name']]))
                        ->default(Sites::getFirstSite()['id'] ?? null)
                        ->required()
                        ->hidden(fn () => count(Sites::getSites()) < 2),
                    TextInput::make('name')
                        ->label(__('Naam'))
                        ->helperText(__('De naam van de cookie. Gebruik * voor een reeks, bijvoorbeeld _ga_*.'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('provider')
                        ->label(__('Aanbieder'))
                        ->helperText(__('Wie de cookie plaatst, bijvoorbeeld Google Analytics of de naam van de website.'))
                        ->maxLength(255),
                    Select::make('category')
                        ->label(__('Categorie'))
                        ->options(Cookie::categoryLabels())
                        ->helperText(__('Bepaalt onder welke kop de cookie op de cookiepagina staat, en voor welke toestemming de cookie bij intrekken wordt opgeruimd.'))
                        ->required(),
                    Select::make('type')
                        ->label(__('Soort'))
                        ->options(Cookie::typeLabels())
                        ->default('cookie')
                        ->required(),
                    TextInput::make('purpose')
                        ->label(__('Doel'))
                        ->helperText(__('In gewone taal: waarvoor wordt deze cookie gebruikt?'))
                        ->maxLength(500),
                    TextInput::make('duration')
                        ->label(__('Bewaartermijn'))
                        ->helperText(__('Bijvoorbeeld: 1 jaar, 30 dagen of Sessie.'))
                        ->maxLength(100),
                    TextInput::make('privacy_url')
                        ->label(__('Privacyverklaring van de aanbieder'))
                        ->url()
                        ->maxLength(255),
                    Toggle::make('visible')
                        ->label(__('Tonen op de cookiepagina'))
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('category')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Naam'))
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->label(__('Aanbieder'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label(__('Categorie'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Cookie::categoryLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'necessary' => 'gray',
                        'preferences' => 'info',
                        'analytics' => 'success',
                        'marketing' => 'primary',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('purpose')
                    ->label(__('Doel'))
                    ->limit(60)
                    ->placeholder(__('Nog geen omschrijving'))
                    ->wrap(),
                TextColumn::make('duration')
                    ->label(__('Bewaartermijn'))
                    ->placeholder('-'),
                TextColumn::make('source')
                    ->label(__('Bron'))
                    ->formatStateUsing(fn (string $state): string => Cookie::sourceLabels()[$state] ?? $state)
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                IconColumn::make('visible')
                    ->label(__('Op cookiepagina'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('site_id')
                    ->label(__('Site'))
                    ->toggleable(isToggledHiddenByDefault: count(Sites::getSites()) < 2),
                TextColumn::make('last_seen_at')
                    ->label(__('Laatst gezien bij scan'))
                    ->since()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label(__('Categorie'))
                    ->options(Cookie::categoryLabels()),
                SelectFilter::make('source')
                    ->label(__('Bron'))
                    ->options(Cookie::sourceLabels()),
                SelectFilter::make('site_id')
                    ->label(__('Site'))
                    ->options(collect(Sites::getSites())->mapWithKeys(fn ($site) => [$site['id'] => $site['name']]))
                    ->hidden(fn () => count(Sites::getSites()) < 2),
            ])
            ->recordActions([
                Action::make('aiSuggest')
                    ->label(__('Voorstel met AI'))
                    ->icon('heroicon-o-sparkles')
                    ->color('gray')
                    ->visible(fn (Cookie $record) => static::canEdit($record)
                        && CookieClassifier::available()
                        && ($record->category === Cookie::UNCLASSIFIED || blank($record->purpose)))
                    ->modalHeading(fn (Cookie $record) => __('Voorstel voor :name', ['name' => $record->name]))
                    ->modalDescription(__('Dit is een voorstel van de AI. Controleer het voordat je opslaat: het staat daarna op de cookiepagina.'))
                    ->fillForm(function (Cookie $record) {
                        $suggestion = app(CookieClassifier::class)->suggest($record);

                        if (! $suggestion) {
                            Notification::make()->title(__('De AI gaf geen bruikbaar voorstel'))->warning()->send();

                            return [];
                        }

                        return [
                            'category' => $suggestion['category'],
                            'provider' => $suggestion['provider'],
                            'purpose_nl' => $suggestion['purpose']['nl'] ?? null,
                            'purpose_en' => $suggestion['purpose']['en'] ?? null,
                            'duration_nl' => $suggestion['duration']['nl'] ?? null,
                            'duration_en' => $suggestion['duration']['en'] ?? null,
                        ];
                    })
                    ->schema([
                        Select::make('category')->label(__('Categorie'))->options(Cookie::categoryLabels())->required(),
                        TextInput::make('provider')->label(__('Aanbieder')),
                        TextInput::make('purpose_nl')->label(__('Doel (Nederlands)')),
                        TextInput::make('purpose_en')->label(__('Doel (Engels)')),
                        TextInput::make('duration_nl')->label(__('Bewaartermijn (Nederlands)')),
                        TextInput::make('duration_en')->label(__('Bewaartermijn (Engels)')),
                    ])
                    ->action(function (Cookie $record, array $data) {
                        app(CookieClassifier::class)->apply($record, [
                            'category' => $data['category'],
                            'provider' => $data['provider'] ?? null,
                            'purpose' => array_filter(['nl' => $data['purpose_nl'] ?? null, 'en' => $data['purpose_en'] ?? null]),
                            'duration' => array_filter(['nl' => $data['duration_nl'] ?? null, 'en' => $data['duration_en'] ?? null]),
                        ]);

                        Notification::make()->title(__('Opgeslagen'))->success()->send();
                    }),
                EditAction::make()->button(),
                DeleteAction::make()
                    ->visible(fn (Cookie $record) => $record->source !== 'builtin')
                    ->button(),
                Action::make('hide')
                    ->label(fn (Cookie $record) => $record->visible ? __('Verbergen') : __('Tonen'))
                    ->icon(fn (Cookie $record) => $record->visible ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->visible(fn (Cookie $record) => $record->source === 'builtin')
                    ->action(function (Cookie $record) {
                        $record->update(['visible' => ! $record->visible, 'customized' => true]);
                        Notification::make()->title(__('Opgeslagen'))->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('aiClassify')
                        ->label(__('Indelen met AI'))
                        ->icon('heroicon-o-sparkles')
                        ->visible(fn () => CookieClassifier::available())
                        ->requiresConfirmation()
                        ->modalDescription(__('De AI deelt de geselecteerde cookies zonder categorie of omschrijving in en schrijft een omschrijving. Dat komt meteen op de cookiepagina; loop het daarna even na.'))
                        ->action(function ($records) {
                            $classifier = app(CookieClassifier::class);
                            $done = 0;

                            foreach ($records as $record) {
                                if ($record->category !== Cookie::UNCLASSIFIED && filled($record->purpose)) {
                                    continue;
                                }

                                if ($suggestion = $classifier->suggest($record)) {
                                    $classifier->apply($record, $suggestion);
                                    $done++;
                                }
                            }

                            Notification::make()->title(__(':count cookies ingedeeld', ['count' => $done]))->success()->send();
                        }),
                    DeleteBulkAction::make()
                        ->action(fn ($records) => $records->where('source', '!=', 'builtin')->each->delete()),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCookies::route('/'),
            'create' => CreateCookie::route('/create'),
            'edit' => EditCookie::route('/{record}/edit'),
        ];
    }
}
