# Changelog

All notable changes to `dashed-cookies` will be documented in this file.

## v4.0.1 - 2026-11-19

### Added
- Engelse vertaling van de beheerteksten (`resources/lang/en.json`).

## v4.0.0 - 2026-11-19

### Added
- Eigen cookiebanner per site (standaard uit) met Google Consent Mode v2: GTM, GA, de Meta-pixel en de extra scripts wachten op toestemming via `ConsentGate` in dashed-core 4.115. Blok in de hoek, balk of venster, kleuren, afronding, logo en teksten per taal; live voorbeeld en contrastcontrole (WCAG AA) op het instellingenscherm.
- Video's en kaarten (YouTube, Vimeo, Google Maps, Spotify, Instagram, Facebook, TikTok) laden pas na toestemming, met een plaatshouder en "Toch laden"; YouTube via youtube-nocookie.com.
- Global Privacy Control en `DashedConsent.whenConsented()` voor thema's.
- Blok Cookieoverzicht voor de cookiepagina, gevuld uit de ingestelde diensten, een wekelijkse HTTP-scan en handmatige regels; cookies per categorie ook in het instellingenvenster.
- AI-voorstel en bulkindeling voor onbekende cookies, melding aan de beheerders als een scan iets nieuws vindt.
- Toestemmingslogboek als bewijs (gepseudonimiseerd IP), bewaartermijn 3 jaar, met statistieken en een grafiek per week.
- Controles op de Beveiligingscheck en in de Sitescan, en `dashed:cookies:migrate-banner` voor de overstap van Cookiebot, CookieYes of spatie.
