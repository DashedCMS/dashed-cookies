{{--
    Staat als allereerste in de head, vóór elk trackingscript.

    1. Google Consent Mode v2: alles behalve beveiliging staat standaard op
       denied. Een terugkerende bezoeker met een geldige keuze krijgt meteen
       een update, nog voordat GTM of gtag.js laadt.
    2. De lader: zet scripts met type="text/plain" en data-dashed-consent om
       zodra een van hun categorieën is toegestaan, in documentvolgorde, en
       wacht bij een extern script zonder async tot het geladen is zodat een
       inline script dat erop leunt het al aantreft.
    3. window.DashedConsent: lezen, opslaan en de instellingen openen. De
       banner gebruikt dit, een thema mag het ook.
--}}
<script data-navigate-once>
(function () {
    if (window.DashedConsent) { return; }

    var cfg = @json($config);

    window.dataLayer = window.dataLayer || [];
    function gtag() { window.dataLayer.push(arguments); }
    if (typeof window.gtag !== 'function') { window.gtag = gtag; }

    function read() {
        try {
            var m = document.cookie.match(new RegExp('(?:^|; )' + cfg.cookie + '=([^;]*)'));
            if (!m) { return null; }
            var d = JSON.parse(decodeURIComponent(m[1]));
            if (!d || d.v !== cfg.version || !Array.isArray(d.c)) { return null; }
            return d;
        } catch (e) {
            return null;
        }
    }

    var state = read();

    function granted(category) {
        return category === 'necessary' || !!(state && state.c.indexOf(category) !== -1);
    }

    function signals() {
        var g = function (c) { return granted(c) ? 'granted' : 'denied'; };
        return {
            ad_storage: g('marketing'),
            ad_user_data: g('marketing'),
            ad_personalization: g('marketing'),
            analytics_storage: g('analytics'),
            functionality_storage: g('preferences'),
            personalization_storage: g('preferences'),
            security_storage: 'granted'
        };
    }

    gtag('consent', 'default', {
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        analytics_storage: 'denied',
        functionality_storage: 'denied',
        personalization_storage: 'denied',
        security_storage: 'granted',
        wait_for_update: 500
    });
    gtag('set', 'ads_data_redaction', true);

    if (state) {
        gtag('consent', 'update', signals());
    }

    // Global Privacy Control: de browser vraagt om niet gevolgd te worden.
    var gpc = !!cfg.respectGpc && navigator.globalPrivacyControl === true;

    var waiting = [];

    function runWaiting() {
        waiting = waiting.filter(function (w) {
            if (!granted(w.category)) { return true; }
            try { w.fn(); } catch (e) {}
            return false;
        });
    }

    function loadEmbed(frame) {
        if (!frame || !frame.getAttribute('data-dashed-consent-src')) { return; }
        frame.src = frame.getAttribute('data-dashed-consent-src');
        frame.removeAttribute('data-dashed-consent-src');
        frame.removeAttribute('data-dashed-consent');
        frame.hidden = false;
        var p = frame.previousElementSibling;
        if (p && p.hasAttribute('data-dashed-embed-placeholder')) { p.parentNode.removeChild(p); }
    }

    function activateEmbeds() {
        Array.prototype.slice.call(document.querySelectorAll('iframe[data-dashed-consent-src]')).forEach(function (f) {
            var cats = (f.getAttribute('data-dashed-consent') || 'marketing').split(/\s+/);
            if (cats.some(granted)) { loadEmbed(f); }
        });
    }

    // "Toch laden" bij een embed laadt alleen die ene, zonder de keuze te wijzigen.
    document.addEventListener('click', function (e) {
        var b = e.target && e.target.closest ? e.target.closest('[data-dashed-embed-load]') : null;
        if (!b) { return; }
        e.preventDefault();
        var p = b.closest('[data-dashed-embed-placeholder]');
        loadEmbed(p ? p.nextElementSibling : null);
    });

    function activate() {
        activateEmbeds();
        runWaiting();
        var list = Array.prototype.slice.call(document.querySelectorAll('script[type="text/plain"][data-dashed-consent]'));
        var next = function (i) {
            if (i >= list.length) { return; }
            var s = list[i];
            var cats = (s.getAttribute('data-dashed-consent') || '').split(/\s+/);
            if (!s.parentNode || !cats.some(granted)) { return next(i + 1); }

            // Ook uitgesteld en de uitstellader heeft nog niet gedraaid: die
            // pakt hem straks zelf op.
            if (s.hasAttribute('data-dashed-delayed') && !window.dashedDelayedDone) {
                s.removeAttribute('data-dashed-consent');
                return next(i + 1);
            }

            var n = document.createElement('script');
            for (var a = 0; a < s.attributes.length; a++) {
                var attr = s.attributes[a];
                if (attr.name === 'type' || attr.name === 'data-dashed-consent' || attr.name === 'data-dashed-delayed') { continue; }
                n.setAttribute(attr.name, attr.value);
            }

            if (s.src) {
                var wait = !s.hasAttribute('async') && !s.hasAttribute('defer');
                n.async = !wait;
                if (wait) {
                    n.onload = n.onerror = function () { next(i + 1); };
                    s.parentNode.replaceChild(n, s);
                    return;
                }
                s.parentNode.replaceChild(n, s);
            } else {
                n.text = s.textContent;
                s.parentNode.replaceChild(n, s);
            }
            next(i + 1);
        };
        next(0);
    }

    function uuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') { return window.crypto.randomUUID(); }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    function deleteCookie(name) {
        var parts = location.hostname.split('.');
        var domains = [''];
        for (var i = 0; i < parts.length - 1; i++) { domains.push('; domain=.' + parts.slice(i).join('.')); }
        domains.forEach(function (d) {
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + d;
        });
    }

    function removeCookiesOf(categories) {
        var existing = document.cookie.split('; ').map(function (c) { return c.split('=')[0]; });
        categories.forEach(function (category) {
            (cfg.cookieNames[category] || []).forEach(function (pattern) {
                var re = new RegExp('^' + pattern.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*') + '$');
                existing.forEach(function (name) { if (re.test(name)) { deleteCookie(name); } });
            });
        });
    }

    function save(categories, action) {
        var allowed = ['necessary'];
        (categories || []).forEach(function (c) {
            if (cfg.categories.indexOf(c) !== -1 && allowed.indexOf(c) === -1) { allowed.push(c); }
        });

        var previous = state ? state.c.slice() : [];
        state = { id: state && state.id ? state.id : uuid(), v: cfg.version, c: allowed, t: Math.floor(Date.now() / 1000) };

        document.cookie = cfg.cookie + '=' + encodeURIComponent(JSON.stringify(state))
            + '; path=/; max-age=' + (cfg.lifetimeDays * 86400) + '; SameSite=Lax'
            + (location.protocol === 'https:' ? '; Secure' : '');

        gtag('consent', 'update', signals());
        window.dataLayer.push({ event: 'dashed_consent_update', dashed_consent: allowed.slice() });

        try {
            fetch(cfg.endpoint, {
                method: 'POST',
                keepalive: true,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ consent_id: state.id, categories: allowed, version: cfg.version, action: action || 'custom', url: location.href, locale: document.documentElement.lang || null })
            });
        } catch (e) {}

        var withdrawn = previous.filter(function (c) { return allowed.indexOf(c) === -1; });
        window.dispatchEvent(new CustomEvent('dashed-consent:updated', { detail: { consent: state, withdrawn: withdrawn } }));

        if (withdrawn.length) {
            // Een script dat al draait is niet meer te stoppen: opruimen en
            // de pagina opnieuw laden zonder.
            removeCookiesOf(withdrawn);
            location.reload();
            return;
        }

        activate();
    }

    window.DashedConsent = {
        config: cfg,
        get: function () { return state; },
        has: granted,
        gpc: gpc,
        // Met GPC zet "Alles accepteren" marketing niet aan; via de
        // instellingen kan de bezoeker het nog wel zelf kiezen.
        acceptAll: function () { save(gpc ? cfg.categories.filter(function (c) { return c !== 'marketing'; }) : cfg.categories, 'accept_all'); },
        rejectAll: function () { save([], 'reject_all'); },
        save: function (categories) { save(categories, 'custom'); },
        open: function () { window.dispatchEvent(new CustomEvent('dashed-consent:open')); },
        // Voor thema's en pakketten: voer fn uit zodra de categorie is
        // toegestaan, meteen als dat al zo is.
        whenConsented: function (category, fn) {
            if (typeof fn !== 'function') { return; }
            if (granted(category)) { try { fn(); } catch (e) {} return; }
            waiting.push({ category: category, fn: fn });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', activate);
    } else {
        activate();
    }
    document.addEventListener('livewire:navigated', activate);
})();
</script>
