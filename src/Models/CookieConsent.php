<?php

namespace Dashed\DashedCookies\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bewijs van een toestemmingskeuze (AVG art. 7 lid 1). Eén rij per keuze,
 * nooit bijgewerkt: een latere keuze van dezelfde bezoeker is een nieuwe rij
 * met hetzelfde consent_id.
 */
class CookieConsent extends Model
{
    public const UPDATED_AT = null;

    public const ACTIONS = ['accept_all', 'reject_all', 'custom', 'withdraw'];

    protected $table = 'dashed__cookie_consents';

    protected $guarded = [];

    protected $casts = [
        'categories' => 'array',
        'version' => 'integer',
        'created_at' => 'datetime',
    ];

    public static function actionLabels(): array
    {
        return [
            'accept_all' => __('Alles geaccepteerd'),
            'reject_all' => __('Alles geweigerd'),
            'custom' => __('Eigen keuze'),
            'withdraw' => __('Ingetrokken'),
        ];
    }

    public static function hashIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }
}
