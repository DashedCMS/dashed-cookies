# dashed-cookies

Cookiebanner, cookieoverzicht en toestemmingslogboek voor Dashed CMS.

- Banner per site aan te zetten bij Instellingen, Cookies (standaard uit).
- Google Consent Mode v2, en GTM, GA, de Meta-pixel en de extra scripts wachten op toestemming.
- Blok "Cookieoverzicht" voor de cookiepagina, automatisch gevuld uit de ingestelde diensten en een wekelijkse scan.
- Elke keuze wordt als bewijs vastgelegd (Cookietoestemmingen).
- Footerlink: `<x-dashed-cookies::settings-link/>` of een element met `data-cookie-settings`.

Vereist dashed-core met `ConsentGate` (v4.115 of hoger).

Na installeren: `php artisan migrate`, en `schedule:run` op de server voor de dagelijkse
synchronisatie en de wekelijkse scan.

Meer in `docs/claude/cookies.md` van de monorepo.
