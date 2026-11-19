<?php

namespace Dashed\DashedCookies\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCookies\Models\Cookie;
use Dashed\DashedCookies\Models\CookieConsent;

/**
 * Legt een toestemmingskeuze vast als bewijs. De keuze zelf staat al in het
 * cookie van de bezoeker; dit endpoint mag dus falen zonder dat de banner
 * stuk is.
 *
 * Bewust buiten de web-groep: geen sessie en geen CSRF-token, want een
 * pagina uit de responscache draagt een oud token en zou dan een 419
 * krijgen. Het ergste dat een vervalst verzoek kan doen is een extra regel
 * in het logboek, en de throttle begrenst dat.
 */
class ConsentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'consent_id' => ['required', 'uuid'],
            'categories' => ['present', 'array', 'max:4'],
            'categories.*' => ['string', 'in:'.implode(',', Cookie::CATEGORIES)],
            'version' => ['required', 'integer', 'min:1', 'max:100000'],
            'action' => ['required', 'string', 'in:'.implode(',', CookieConsent::ACTIONS)],
            'url' => ['nullable', 'string', 'max:2000'],
            'locale' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z_-]+$/'],
        ]);

        CookieConsent::query()->create([
            'consent_id' => $data['consent_id'],
            'site_id' => Sites::getActive(),
            // De route draait buiten de front-endmiddleware, dus de taal komt
            // van het lang-attribuut van de pagina.
            'locale' => $data['locale'] ?? null,
            'action' => $data['action'],
            'categories' => array_values(array_unique(array_merge(['necessary'], $data['categories']))),
            'version' => $data['version'],
            'ip_hash' => CookieConsent::hashIp($request->ip()),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 200),
            'url' => $this->cleanUrl($data['url'] ?? null),
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true], 201);
    }

    /**
     * Alleen pad zonder querystring: daar kan een e-mailadres of token in
     * staan, en dat hoort niet in een bewijslogboek.
     */
    protected function cleanUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        return mb_substr(($parts['scheme'] ?? 'https').'://'.$parts['host'].($parts['path'] ?? '/'), 0, 500);
    }
}
