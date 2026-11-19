<?php

namespace Dashed\DashedCookies\Classes;

use Illuminate\Support\Carbon;
use Dashed\DashedCookies\Models\CookieConsent;

/**
 * Cijfers over de gegeven toestemming, allemaal tellingen in de database.
 * Een percentage is null als er niets te tellen valt: nul procent tonen waar
 * niet gemeten is, klopt niet.
 */
class ConsentStatistics
{
    /**
     * @return array{total: int, accept_all: ?float, reject_all: ?float, custom: ?float, analytics: ?float, marketing: ?float}
     */
    public static function summary(int $days = 30, ?string $siteId = null): array
    {
        $query = fn () => CookieConsent::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->where('action', '!=', 'withdraw')
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId));

        $total = $query()->count();
        $percentage = fn (int $count) => $total > 0 ? round($count / $total * 100, 1) : null;

        $perAction = $query()->selectRaw('action, count(*) as aantal')->groupBy('action')->pluck('aantal', 'action');

        return [
            'total' => $total,
            'accept_all' => $percentage((int) ($perAction['accept_all'] ?? 0)),
            'reject_all' => $percentage((int) ($perAction['reject_all'] ?? 0)),
            'custom' => $percentage((int) ($perAction['custom'] ?? 0)),
            'analytics' => $percentage($query()->where('categories', 'like', '%"analytics"%')->count()),
            'marketing' => $percentage($query()->where('categories', 'like', '%"marketing"%')->count()),
        ];
    }

    /**
     * Keuzes per week, voor de grafiek.
     *
     * @return array{labels: array<int, string>, accept_all: array<int, int>, reject_all: array<int, int>, custom: array<int, int>}
     */
    public static function perWeek(int $weeks = 12, ?string $siteId = null): array
    {
        $start = now()->startOfWeek()->subWeeks($weeks - 1);

        $rows = CookieConsent::query()
            ->where('created_at', '>=', $start)
            ->whereIn('action', ['accept_all', 'reject_all', 'custom'])
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->selectRaw('DATE(created_at) as dag, action, count(*) as aantal')
            ->groupBy('dag', 'action')
            ->get();

        $result = ['labels' => [], 'accept_all' => [], 'reject_all' => [], 'custom' => []];
        $index = [];

        for ($i = 0; $i < $weeks; $i++) {
            $week = $start->copy()->addWeeks($i);
            $index[$week->format('o-W')] = $i;
            $result['labels'][] = $week->format('d-m');
            $result['accept_all'][] = 0;
            $result['reject_all'][] = 0;
            $result['custom'][] = 0;
        }

        foreach ($rows as $row) {
            $key = Carbon::parse($row->dag)->format('o-W');

            if (isset($index[$key])) {
                $result[$row->action][$index[$key]] += (int) $row->aantal;
            }
        }

        return $result;
    }
}
