<?php

namespace Dashed\DashedCookies\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Dashed\DashedCookies\Classes\ConsentStatistics;

class ConsentChartWidget extends ChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public function getHeading(): ?string
    {
        return __('Keuzes per week');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $data = ConsentStatistics::perWeek(12);

        return [
            'labels' => $data['labels'],
            'datasets' => [
                ['label' => __('Alles geaccepteerd'), 'data' => $data['accept_all'], 'backgroundColor' => '#16a34a'],
                ['label' => __('Eigen keuze'), 'data' => $data['custom'], 'backgroundColor' => '#2563eb'],
                ['label' => __('Alles geweigerd'), 'data' => $data['reject_all'], 'backgroundColor' => '#dc2626'],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return ['scales' => ['x' => ['stacked' => true], 'y' => ['stacked' => true, 'beginAtZero' => true]]];
    }
}
