@php
    use Dashed\DashedCookies\Classes\CookieSettings;
    use Dashed\DashedCookies\Classes\CookieOverview;

    $siteId = CookieSettings::siteId();
    $groups = CookieOverview::grouped($siteId);
    $typeLabels = CookieOverview::typeLabels();
    $useContainer = \Illuminate\Support\Facades\View::exists('components.container');
    $inContainer = (bool) ($data['in_container'] ?? true);
@endphp
{{--
    Blok "Cookieoverzicht". Een thema kan dit overschrijven met een eigen
    resources/views/components/blocks/cookie-overview.blade.php; deze versie
    staat in een extra viewlocatie achteraan en wint dus nooit van het project.
--}}
<section class="dco" style="@if($data['top_margin'] ?? true) padding-top: clamp(40px,5vw,72px); @endif @if($data['bottom_margin'] ?? true) padding-bottom: clamp(40px,5vw,72px); @endif">
    <style>
        .dco-inner { max-width: 1340px; margin: 0 auto; padding: 0 clamp(16px,4vw,48px); }
        .dco h2 { font-size: 1.5em; font-weight: 700; margin: 0 0 .5em; }
        .dco h3 { font-size: 1.2em; font-weight: 700; margin: 1.6em 0 .3em; }
        .dco-desc { margin: 0 0 .8em; }
        .dco-table-wrap { overflow-x: auto; }
        .dco table { width: 100%; border-collapse: collapse; font-size: .92em; }
        .dco th, .dco td { text-align: left; vertical-align: top; padding: .55em .75em; border-bottom: 1px solid rgba(127,127,127,.25); }
        .dco th { font-weight: 600; white-space: nowrap; }
        .dco code { font-size: .95em; word-break: break-all; }
        .dco-button { display: inline-block; margin-top: 1.4em; padding: .6em 1.1em; border: 2px solid currentColor; border-radius: 6px; background: transparent; color: inherit; font: inherit; font-weight: 600; cursor: pointer; }
        .dco-updated { margin-top: 1em; font-size: .85em; opacity: .75; }
    </style>
    @if($useContainer)
        <x-container :show="$inContainer">
            @include('dashed-cookies::partials.cookie-overview-body')
        </x-container>
    @else
        <div @class(['dco-inner' => $inContainer])>
            @include('dashed-cookies::partials.cookie-overview-body')
        </div>
    @endif
</section>
