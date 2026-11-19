@php
    $logoUrl = $logo ? (mediaHelper()->getSingleMedia($logo, ['widen' => 240])->url ?? null) : null;
    $toggleCategories = $categories;
@endphp
{{--
    De cookiebanner. Eigen stijlblok met dcb-klassen in plaats van Tailwind,
    omdat niet elk klantproject vendor/dashed meecompileert. Geen Alpine:
    de banner moet ook werken op een pagina zonder Livewire.

    AVG: weigeren staat op het eerste scherm en is even groot als accepteren,
    geen vinkje staat vooraf aan, en de instellingen zijn altijd terug te
    halen (zwevende knop, of een element met data-cookie-settings).
--}}
<div id="dcb-root" class="dcb dcb--{{ $layout }} dcb--{{ $position }}"
     style="--dcb-primary: {{ $colors['primary'] }}; --dcb-primary-text: {{ $colors['primary_text'] }}; --dcb-bg: {{ $colors['background'] }}; --dcb-text: {{ $colors['text'] }}; --dcb-radius: {{ $radius }}px;">
    <style>
        .dcb, .dcb * { box-sizing: border-box; }
        .dcb { font-family: inherit; font-size: 15px; line-height: 1.5; color: var(--dcb-text); }
        .dcb [hidden] { display: none !important; }
        .dcb-banner { position: fixed; z-index: 2147483000; background: var(--dcb-bg); color: var(--dcb-text); box-shadow: 0 10px 40px rgba(0,0,0,.18); padding: 20px; }
        .dcb--box .dcb-banner { bottom: 16px; max-width: 420px; width: calc(100% - 32px); border-radius: var(--dcb-radius); }
        .dcb--box.dcb--left .dcb-banner { left: 16px; }
        .dcb--box.dcb--right .dcb-banner { right: 16px; }
        .dcb--bar .dcb-banner { left: 0; right: 0; bottom: 0; border-radius: var(--dcb-radius) var(--dcb-radius) 0 0; }
        .dcb--bar .dcb-banner-inner { max-width: 1200px; margin: 0 auto; display: flex; gap: 24px; align-items: center; flex-wrap: wrap; }
        .dcb--bar .dcb-banner-body { flex: 1 1 420px; }
        .dcb--modal .dcb-banner { top: 50%; left: 50%; transform: translate(-50%, -50%); max-width: 520px; width: calc(100% - 32px); border-radius: var(--dcb-radius); }
        .dcb-backdrop { position: fixed; inset: 0; z-index: 2147482999; background: rgba(0,0,0,.45); }
        .dcb-logo { max-height: 32px; width: auto; margin-bottom: 10px; display: block; }
        .dcb-title { font-size: 17px; font-weight: 700; margin: 0 0 6px; color: var(--dcb-text); }
        .dcb-text { margin: 0; font-size: 14px; }
        .dcb-text a, .dcb-link { color: var(--dcb-text); text-decoration: underline; }
        .dcb-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
        .dcb--bar .dcb-actions { margin-top: 0; }
        .dcb-btn { appearance: none; border: 2px solid var(--dcb-primary); background: var(--dcb-primary); color: var(--dcb-primary-text); font: inherit; font-size: 14px; font-weight: 600; padding: 9px 16px; border-radius: calc(var(--dcb-radius) * .6); cursor: pointer; flex: 1 1 auto; text-align: center; }
        .dcb-btn--ghost { background: transparent; color: var(--dcb-text); border-color: transparent; text-decoration: underline; flex: 0 0 auto; }
        .dcb-btn:focus-visible, .dcb-switch input:focus-visible + span, .dcb-float:focus-visible { outline: 3px solid var(--dcb-primary); outline-offset: 2px; }
        .dcb-prefs { position: fixed; z-index: 2147483001; top: 50%; left: 50%; transform: translate(-50%, -50%); width: calc(100% - 32px); max-width: 560px; max-height: calc(100vh - 32px); overflow-y: auto; background: var(--dcb-bg); color: var(--dcb-text); border-radius: var(--dcb-radius); box-shadow: 0 20px 60px rgba(0,0,0,.3); padding: 24px; }
        .dcb-cat { border-top: 1px solid rgba(127,127,127,.25); padding: 14px 0; }
        .dcb-cat-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .dcb-cat-label { font-weight: 600; }
        .dcb-cat-desc { margin: 4px 0 0; font-size: 13px; opacity: .85; }
        .dcb-always { font-size: 13px; font-weight: 600; opacity: .75; }
        .dcb-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex: 0 0 auto; }
        .dcb-switch input { position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer; }
        .dcb-switch span { position: absolute; inset: 0; border-radius: 24px; background: rgba(127,127,127,.45); transition: background .15s; pointer-events: none; }
        .dcb-switch span::after { content: ""; position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; border-radius: 50%; background: #fff; transition: transform .15s; }
        .dcb-switch input:checked + span { background: var(--dcb-primary); }
        .dcb-switch input:checked + span::after { transform: translateX(20px); }
        .dcb-gpc { margin-top: 8px; font-size: 13px; opacity: .85; }
        .dcb-list { margin-top: 6px; font-size: 13px; }
        .dcb-list summary { cursor: pointer; text-decoration: underline; }
        .dcb-list ul { margin: 6px 0 0; padding-left: 18px; }
        .dcb-list li { margin-bottom: 4px; }
        .dcb-list code { font-size: 12px; word-break: break-all; }
        .dcb-list-meta { opacity: .75; }
        .dcb-id { margin-top: 12px; font-size: 12px; opacity: .7; word-break: break-all; }
        .dcb-float { position: fixed; z-index: 2147482998; bottom: 16px; width: 44px; height: 44px; border-radius: 50%; border: 0; background: var(--dcb-primary); color: var(--dcb-primary-text); box-shadow: 0 4px 16px rgba(0,0,0,.2); cursor: pointer; display: flex; align-items: center; justify-content: center; padding: 0; }
        .dcb--left .dcb-float { left: 16px; }
        .dcb--right .dcb-float { right: 16px; }
        .dcb-float svg { width: 22px; height: 22px; }
        @media (max-width: 480px) { .dcb-actions { flex-direction: column; } .dcb-btn { width: 100%; } }
        @media (prefers-reduced-motion: reduce) { .dcb-switch span, .dcb-switch span::after { transition: none; } }
    </style>

    @if($layout === 'modal')
        <div class="dcb-backdrop" data-dcb-backdrop hidden></div>
    @endif

    <div class="dcb-banner" data-dcb-banner role="dialog" aria-labelledby="dcb-title" aria-describedby="dcb-text" @if($layout === 'modal') aria-modal="true" @endif hidden>
        <div class="dcb-banner-inner">
            <div class="dcb-banner-body">
                @if($logoUrl)
                    <img class="dcb-logo" src="{{ $logoUrl }}" alt="">
                @endif
                <p class="dcb-title" id="dcb-title">{{ $text('title') }}</p>
                <p class="dcb-text" id="dcb-text">
                    {{ $text('text') }}
                    @if($policyUrl)
                        <a href="{{ $policyUrl }}">{{ $text('policy_link') }}</a>
                    @endif
                </p>
                <p class="dcb-text dcb-gpc" data-dcb-gpc hidden>{{ $text('gpc_notice') }}</p>
            </div>
            <div class="dcb-actions">
                <button type="button" class="dcb-btn" data-dcb-reject>{{ $text('reject') }}</button>
                <button type="button" class="dcb-btn" data-dcb-accept>{{ $text('accept') }}</button>
                <button type="button" class="dcb-btn dcb-btn--ghost" data-dcb-customize>{{ $text('customize') }}</button>
            </div>
        </div>
    </div>

    <div class="dcb-backdrop" data-dcb-prefs-backdrop hidden></div>
    <div class="dcb-prefs" data-dcb-prefs role="dialog" aria-modal="true" aria-labelledby="dcb-prefs-title" hidden tabindex="-1">
        <p class="dcb-title" id="dcb-prefs-title">{{ $text('settings_title') }}</p>
        <p class="dcb-text">
            {{ $text('settings_text') }}
            @if($policyUrl)
                <a href="{{ $policyUrl }}">{{ $text('policy_link') }}</a>
            @endif
        </p>

        <div style="margin-top: 16px;">
            <div class="dcb-cat">
                <div class="dcb-cat-head">
                    <span class="dcb-cat-label">{{ $text('necessary_label') }}</span>
                    <span class="dcb-always">{{ $text('always_on') }}</span>
                </div>
                <p class="dcb-cat-desc">{{ $text('necessary_description') }}</p>
                @if(($cookieGroups['necessary'] ?? collect())->isNotEmpty())
                    <details class="dcb-list">
                        <summary>{{ $text('show_cookies') }} ({{ $cookieGroups['necessary']->count() }})</summary>
                        <ul>
                            @foreach($cookieGroups['necessary'] as $listedCookie)
                                <li><code>{{ $listedCookie->name }}</code> <span class="dcb-list-meta">{{ $listedCookie->provider }}@if($listedCookie->duration) · {{ $listedCookie->duration }}@endif</span>@if($listedCookie->purpose)<br>{{ $listedCookie->purpose }}@endif</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </div>
            @foreach($toggleCategories as $category)
                <div class="dcb-cat">
                    <div class="dcb-cat-head">
                        <label class="dcb-cat-label" for="dcb-cat-{{ $category }}">{{ $text($category.'_label') }}</label>
                        <span class="dcb-switch">
                            <input type="checkbox" id="dcb-cat-{{ $category }}" data-dcb-category="{{ $category }}">
                            <span></span>
                        </span>
                    </div>
                    <p class="dcb-cat-desc">{{ $text($category.'_description') }}</p>
                @if(($cookieGroups[$category] ?? collect())->isNotEmpty())
                    <details class="dcb-list">
                        <summary>{{ $text('show_cookies') }} ({{ $cookieGroups[$category]->count() }})</summary>
                        <ul>
                            @foreach($cookieGroups[$category] as $listedCookie)
                                <li><code>{{ $listedCookie->name }}</code> <span class="dcb-list-meta">{{ $listedCookie->provider }}@if($listedCookie->duration) · {{ $listedCookie->duration }}@endif</span>@if($listedCookie->purpose)<br>{{ $listedCookie->purpose }}@endif</li>
                            @endforeach
                        </ul>
                    </details>
                @endif
                </div>
            @endforeach
        </div>

        <div class="dcb-actions">
            <button type="button" class="dcb-btn" data-dcb-reject>{{ $text('reject') }}</button>
            <button type="button" class="dcb-btn" data-dcb-save>{{ $text('save') }}</button>
            <button type="button" class="dcb-btn" data-dcb-accept>{{ $text('accept') }}</button>
        </div>
        <p class="dcb-id" data-dcb-id-wrap hidden>{{ $text('consent_id_label') }}: <span data-dcb-id></span></p>
    </div>

    @if($floatingButton)
        <button type="button" class="dcb-float" data-cookie-settings aria-label="{{ $text('floating_label') }}" title="{{ $text('floating_label') }}" hidden data-dcb-float>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/>
                <circle cx="8.5" cy="8.5" r="1" fill="currentColor"/>
                <circle cx="16" cy="15.5" r="1" fill="currentColor"/>
                <circle cx="11" cy="17" r="1" fill="currentColor"/>
                <circle cx="7" cy="13" r="1" fill="currentColor"/>
            </svg>
        </button>
    @endif

    <script>
    (function () {
        var root = document.getElementById('dcb-root');
        if (!root || !window.DashedConsent || root.__dcbReady) { return; }
        root.__dcbReady = true;

        var consent = window.DashedConsent;
        var q = function (sel) { return root.querySelector(sel); };
        var qa = function (sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); };
        var banner = q('[data-dcb-banner]');
        var backdrop = q('[data-dcb-backdrop]');
        var prefs = q('[data-dcb-prefs]');
        var prefsBackdrop = q('[data-dcb-prefs-backdrop]');
        var floatBtn = q('[data-dcb-float]');
        var lastFocus = null;

        function show(el, on) { if (el) { el.hidden = !on; } }

        function render() {
            var state = consent.get();
            show(banner, !state && prefs.hidden);
            show(backdrop, !state && prefs.hidden);
            show(floatBtn, !!state);
            var idWrap = q('[data-dcb-id-wrap]');
            if (state && state.id) {
                q('[data-dcb-id]').textContent = state.id;
                show(idWrap, true);
            } else {
                show(idWrap, false);
            }
        }

        function openPrefs() {
            // Na een wire:navigate staat er een nieuwe banner; een oude
            // luisteraar op window hoort dan bij een losgekoppelde root.
            if (!document.body.contains(root)) { return; }
            var state = consent.get();
            qa('[data-dcb-category]').forEach(function (input) {
                // Niets staat vooraf aan zonder eerdere keuze.
                input.checked = !!(state && state.c.indexOf(input.getAttribute('data-dcb-category')) !== -1);
            });
            lastFocus = document.activeElement;
            show(banner, false);
            show(backdrop, false);
            show(prefs, true);
            show(prefsBackdrop, true);
            prefs.focus();
        }

        function closePrefs() {
            show(prefs, false);
            show(prefsBackdrop, false);
            render();
            if (lastFocus && typeof lastFocus.focus === 'function') { lastFocus.focus(); }
        }

        function done() {
            show(prefs, false);
            show(prefsBackdrop, false);
            render();
        }

        qa('[data-dcb-accept]').forEach(function (b) { b.addEventListener('click', function () { consent.acceptAll(); done(); }); });
        qa('[data-dcb-reject]').forEach(function (b) { b.addEventListener('click', function () { consent.rejectAll(); done(); }); });
        qa('[data-dcb-customize]').forEach(function (b) { b.addEventListener('click', openPrefs); });
        q('[data-dcb-save]').addEventListener('click', function () {
            var chosen = qa('[data-dcb-category]').filter(function (i) { return i.checked; }).map(function (i) { return i.getAttribute('data-dcb-category'); });
            consent.save(chosen);
            done();
        });

        prefsBackdrop.addEventListener('click', function () { if (consent.get()) { closePrefs(); } });

        root.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !prefs.hidden && consent.get()) { closePrefs(); }
            if (e.key === 'Tab' && !prefs.hidden) {
                var f = qa('.dcb-prefs button, .dcb-prefs input, .dcb-prefs a').filter(function (el) { return el.offsetParent !== null; });
                if (!f.length) { return; }
                if (e.shiftKey && document.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
                else if (!e.shiftKey && document.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
            }
        });

        // Elk element met data-cookie-settings opent de instellingen, ook in
        // de footer van een thema of op de cookiepagina.
        if (!window.__dcbDelegated) {
            window.__dcbDelegated = true;
            document.addEventListener('click', function (e) {
                var t = e.target && e.target.closest ? e.target.closest('[data-cookie-settings]') : null;
                if (!t) { return; }
                e.preventDefault();
                consent.open();
            });
        }
        window.addEventListener('dashed-consent:open', openPrefs);

        show(q('[data-dcb-gpc]'), !!consent.gpc);

        render();
    })();
    </script>
</div>
