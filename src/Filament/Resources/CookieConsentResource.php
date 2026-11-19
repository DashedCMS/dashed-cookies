<?php

namespace Dashed\DashedCookies\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCookies\Models\Cookie;
use Dashed\DashedCookies\Models\CookieConsent;
use Dashed\DashedCookies\Filament\Resources\CookieConsentResource\Pages\ListCookieConsents;

/**
 * Het bewijs van gegeven toestemming. Vraagt een bezoeker wat hij heeft
 * toegestaan, dan staat zijn toestemmings-ID onderin zijn
 * cookie-instellingen; daarop zoek je hier.
 */
class CookieConsentResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = CookieConsent::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'SEO & site';

    protected static ?string $navigationLabel = 'Cookietoestemmingen';

    protected static ?string $label = 'Cookietoestemming';

    protected static ?string $pluralLabel = 'Cookietoestemmingen';

    protected static ?int $navigationSort = 81;

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $categoryLabels = Cookie::categoryLabels();

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Tijd'))
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable(),
                TextColumn::make('action')
                    ->label(__('Keuze'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CookieConsent::actionLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'accept_all' => 'success',
                        'reject_all', 'withdraw' => 'danger',
                        default => 'info',
                    }),
                TextColumn::make('categories')
                    ->label(__('Toegestaan'))
                    ->formatStateUsing(fn ($state): string => collect((array) $state)->map(fn ($c) => $categoryLabels[$c] ?? $c)->implode(', ')),
                TextColumn::make('consent_id')
                    ->label(__('Toestemmings-ID'))
                    ->fontFamily('mono')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('version')
                    ->label(__('Versie'))
                    ->toggleable(),
                TextColumn::make('url')
                    ->label(__('Pagina'))
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('locale')
                    ->label(__('Taal'))
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user_agent')
                    ->label(__('Browser'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label(__('Keuze'))
                    ->options(CookieConsent::actionLabels()),
                Filter::make('periode')
                    ->schema([
                        DatePicker::make('vanaf')->label(__('Vanaf')),
                        DatePicker::make('tot')->label(__('Tot en met')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['vanaf'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['tot'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    /**
     * Alleen via getWidgets() kent Filament een resourcewidget als
     * Livewire-component; zonder dat herlaadt de pagina eindeloos.
     */
    public static function getWidgets(): array
    {
        return [
            \Dashed\DashedCookies\Filament\Widgets\ConsentStatsWidget::class,
            \Dashed\DashedCookies\Filament\Widgets\ConsentChartWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCookieConsents::route('/'),
        ];
    }
}
