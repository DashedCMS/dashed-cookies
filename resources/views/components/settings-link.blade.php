{{--
    Link of knop voor in de footer van een thema:
    <x-dashed-cookies::settings-link class="underline"/>
    Opent de cookie-instellingen. Rendert niets als het toestemmingsbeheer
    uit staat, zodat een thema hem altijd kan plaatsen.
--}}
@if(\Dashed\DashedCore\Classes\Consent\ConsentGate::active())
    <button type="button" data-cookie-settings {{ $attributes->merge(['style' => 'background:none;border:0;padding:0;font:inherit;color:inherit;cursor:pointer;']) }}>
        {{ $slot->isEmpty() ? \Dashed\DashedCookies\Classes\CookieSettings::text('floating_label') : $slot }}
    </button>
@endif
