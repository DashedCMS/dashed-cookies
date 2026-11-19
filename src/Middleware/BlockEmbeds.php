<?php

namespace Dashed\DashedCookies\Middleware;

use Closure;
use Illuminate\Http\Request;
use Dashed\DashedCookies\Classes\EmbedBlocker;
use Symfony\Component\HttpFoundation\Response;
use Dashed\DashedCore\Classes\Consent\ConsentGate;

/**
 * Front-endmiddleware (cms()->builder('frontendMiddlewares')). Staat binnen
 * de responscache, dus de opgeslagen pagina is al herschreven en voor elke
 * bezoeker gelijk; de lader in de browser zet een embed pas aan.
 */
class BlockEmbeds
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! ConsentGate::active() || ! \Dashed\DashedCookies\Classes\CookieSettings::blockEmbeds()) {
            return $response;
        }

        if (! str_contains((string) $response->headers->get('Content-Type', ''), 'text/html')) {
            return $response;
        }

        if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse || $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || stripos($content, '<iframe') === false) {
            return $response;
        }

        $response->setContent(EmbedBlocker::rewrite($content));

        return $response;
    }
}
