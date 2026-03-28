# Cleanmasterzz Calculator

**De alles-in-één WordPress plugin voor schoonmaakbedrijven.**
Prijscalculator · Boekingsbeheer · Klantportaal · Analytics · PDF Facturen · Licentiesysteem

---

## Wat is het?

Cleanmasterzz Calculator is een krachtige WordPress plugin speciaal gebouwd voor schoonmaakbedrijven. Van een slimme prijscalculator op je website tot een volledig klantportaal met berichtensysteem — alles zit erin.

---

## Functies per abonnement

### Gratis
- Prijscalculator op je website (oppervlakte, diensten, werkgebieden)
- Boekingsbeheer in WordPress admin
- E-mailnotificaties bij nieuwe boeking
- Basis klantportaal (boeking opzoeken via e-mail)
- Kortingscodes
- 1 bedrijfsprofiel

### Pro
Alles van Gratis, plus:
- **Bedrijfswizard** — uitgebreide bedrijfsinstellingen
- **Analytics dashboard** — omzet, boekingen, populaire diensten
- **PDF facturen** — automatisch gegenereerd bij boeking
- **Geavanceerde kortingen** — percentages, vaste bedragen, tijdgebonden
- **Kalenderweergave** — boekingen in kalender
- **Multi-bedrijf** — meerdere vestigingen beheren

### Boss
Alles van Pro, plus:
- **Klantaccounts** — klanten registreren en inloggen
- **Berichtensysteem** — klant ↔ bedrijf communicatie
- **Boss portaal** — volledig klantdashboard met boekingsoverzicht
- **SMS notificaties**
- **Medewerkersinlog per vestiging**

### Agency
Alles van Boss, plus:
- **White-label** — eigen branding, geen Cleanmasterzz vermeldingen
- **Reseller dashboard** — meerdere klanten beheren
- **Onbeperkt bedrijven**

---

## Installatie

1. Upload de plugin naar `/wp-content/plugins/cleanmasterzz-calculator/`
2. Activeer via **Plugins** in WordPress admin
3. Ga naar **Calculator** → Setup wizard en volg de stappen
4. Voeg de calculator toe aan een pagina met shortcode `[cmcalc_calculator]`

### Shortcodes

| Shortcode | Beschrijving |
|-----------|-------------|
| `[cmcalc_calculator]` | Prijscalculator |
| `[cmcalc_portal]` | Basis klantportaal (gratis) |
| `[cmcalc_boss_portal]` | Boss klantportaal met accounts |
| `[cmcalc_boss_login]` | Inlog/registreerformulier |

---

## Licentie activeren

1. Ga naar **Calculator** → **Licentie**
2. Voer je licentiesleutel in (formaat: `CMCALC-XXXX-XXXX-XXXX-XXXX`)
3. Klik **Activeren**

Licenties aanschaffen of beheren: [cleanmasterzz.nl](https://cleanmasterzz.nl)

---

## Vereisten

- WordPress 6.0 of hoger
- PHP 7.4 of hoger
- MySQL 5.7 of hoger

---

## Licentieserver (zelf hosten)

De plugin communiceert met een externe licentieserver. Voor zelf-hosting:

1. Kopieer de map `license-server/` naar je server (bijv. `/var/www/licenses/`)
2. Pas `license-server/config.php` aan met je database- en admingegevens
3. Kopieer `licenses-api.php` naar je WordPress root (`/var/www/html/`)
4. Stel nginx in volgens de configuratie in `license-server/`

Admin panel bereikbaar via `/licenses/admin/` met de inloggegevens uit `config.php`.

---

## Screenshots

| | |
|---|---|
| **Calculator** — klant vult oppervlakte en diensten in | **Boekingen** — volledig overzicht in admin |
| **Analytics** — omzet en trends in één oogopslag | **Boss portaal** — klant ziet al zijn boekingen |

---

## Changelog

### v1.2.0
- Boss portaal met klantaccounts en berichtensysteem
- PDF facturen generatie
- Analytics dashboard
- Licentiesysteem met tiered features
- Multi-bedrijf ondersteuning

### v1.1.0
- SMTP e-mail configuratie
- Klantportaal basis versie
- Beveiligingsfixes

### v1.0.0
- Eerste release
- Prijscalculator, boekingen, kortingscodes

---

## Licentie

Proprietary — alle rechten voorbehouden aan CleanMasterzz.
Zie [cleanmasterzz.nl](https://cleanmasterzz.nl) voor licentievoorwaarden.
