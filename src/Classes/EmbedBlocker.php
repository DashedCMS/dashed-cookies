<?php

namespace Dashed\DashedCookies\Classes;

/**
 * Houdt ingesloten video's en kaarten tegen tot er toestemming is. Een iframe
 * van een bekende dienst krijgt zijn src in data-dashed-consent-src, en
 * ervoor komt een plaatshouder met een knop. De lader in de head zet de src
 * terug zodra de categorie is toegestaan, of als de bezoeker op de knop
 * drukt (alleen die ene embed).
 *
 * Werkt op de hele HTML-respons, dus ook op video's in een eigen themablok
 * of in de rich editor. YouTube krijgt meteen het nocookie-domein, zodat ook
 * een geladen video geen trackingcookies zet voordat hij speelt.
 */
class EmbedBlocker
{
    /**
     * Herkenning op de src, met de naam die de plaatshouder toont en de
     * categorie waarvoor hij laadt.
     */
    public const PROVIDERS = [
        'youtube' => ['pattern' => '#^(https?:)?//(www\.)?(youtube\.com|youtube-nocookie\.com)/embed/#i', 'name' => 'YouTube', 'category' => 'marketing'],
        'vimeo' => ['pattern' => '#^(https?:)?//player\.vimeo\.com/#i', 'name' => 'Vimeo', 'category' => 'analytics'],
        'google_maps' => ['pattern' => '#^(https?:)?//(www\.|maps\.)?google\.[a-z.]+/maps#i', 'name' => 'Google Maps', 'category' => 'marketing'],
        'spotify' => ['pattern' => '#^(https?:)?//open\.spotify\.com/embed#i', 'name' => 'Spotify', 'category' => 'marketing'],
        'instagram' => ['pattern' => '#^(https?:)?//(www\.)?instagram\.com/#i', 'name' => 'Instagram', 'category' => 'marketing'],
        'facebook' => ['pattern' => '#^(https?:)?//(www\.)?facebook\.com/plugins/#i', 'name' => 'Facebook', 'category' => 'marketing'],
        'tiktok' => ['pattern' => '#^(https?:)?//(www\.)?tiktok\.com/embed#i', 'name' => 'TikTok', 'category' => 'marketing'],
    ];

    public static function rewrite(string $html, ?string $siteId = null, ?string $locale = null): string
    {
        if ($html === '' || stripos($html, '<iframe') === false) {
            return $html;
        }

        $button = e(CookieSettings::text('embed_button', $siteId, $locale));
        $template = CookieSettings::text('embed_text', $siteId, $locale);
        $settingsLabel = e(CookieSettings::text('customize', $siteId, $locale));

        return (string) preg_replace_callback('/<iframe\b([^>]*)>/i', function (array $match) use ($button, $template, $settingsLabel): string {
            $attributes = $match[1];

            if (preg_match('/\sdata-dashed-consent/i', $attributes) || preg_match('/\sdata-cookie-category\s*=\s*["\']?necessary/i', $attributes)) {
                return $match[0];
            }

            if (! preg_match('/\ssrc\s*=\s*(["\'])(.*?)\1/is', $attributes, $src)) {
                return $match[0];
            }

            $url = html_entity_decode($src[2]);
            $provider = null;

            foreach (self::PROVIDERS as $key => $definition) {
                if (preg_match($definition['pattern'], $url)) {
                    $provider = $definition;

                    break;
                }
            }

            if (! $provider) {
                return $match[0];
            }

            $url = (string) preg_replace('#//(www\.)?youtube\.com/embed/#i', '//www.youtube-nocookie.com/embed/', $url);

            $category = $provider['category'];

            if (preg_match('/\sdata-cookie-category\s*=\s*["\']?([a-z]+)/i', $attributes, $chosen)) {
                $category = strtolower($chosen[1]);
            }

            $attributes = str_replace($src[0], ' src="about:blank" data-dashed-consent-src="'.e($url).'"', $attributes);
            $attributes .= ' data-dashed-consent="'.e($category).'" hidden';

            $text = e(str_replace(':provider:', $provider['name'], $template));

            $placeholder = '<span class="dce-embed" data-dashed-embed-placeholder style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;min-height:180px;padding:20px;text-align:center;background:#f3f4f6;color:#1f2937;border-radius:8px;font-size:14px;line-height:1.5;">'
                .'<span>'.$text.'</span>'
                .'<span style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center;">'
                .'<button type="button" data-dashed-embed-load style="appearance:none;border:0;background:#111827;color:#fff;font:inherit;font-weight:600;padding:8px 14px;border-radius:6px;cursor:pointer;">'.$button.'</button>'
                .'<button type="button" data-cookie-settings style="appearance:none;border:0;background:none;color:inherit;font:inherit;text-decoration:underline;cursor:pointer;">'.$settingsLabel.'</button>'
                .'</span></span>';

            return $placeholder.'<iframe'.$attributes.'>';
        }, $html);
    }
}
