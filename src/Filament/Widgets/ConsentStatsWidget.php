<?php

namespace Dashed\DashedCookies\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Dashed\DashedCookies\Classes\ConsentStatistics;

class ConsentStatsWidget extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $stats = ConsentStatistics::summary(30);
        $pct = fn (?float $value) => $value === null ? '-' : number_format($value, 1, ',', '.').'%';

        return [
            Stat::make(__('Keuzes (30 dagen)'), number_format($stats['total'], 0, ',', '.')),
            Stat::make(__('Alles geaccepteerd'), $pct($stats['accept_all']))->color('success'),
            Stat::make(__('Alles geweigerd'), $pct($stats['reject_all']))->color('danger'),
            Stat::make(__('Statistieken toegestaan'), $pct($stats['analytics']))
                ->description(__('Zoveel bezoekers telt Google Analytics nog volledig mee')),
            Stat::make(__('Marketing toegestaan'), $pct($stats['marketing']))
                ->description(__('Zoveel bezoekers zijn nog te volgen voor advertenties')),
        ];
    }
}
