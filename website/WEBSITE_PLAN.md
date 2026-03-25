# CleanMasterzz — Website Design Plan
## Concept: "OBSIDIAN" — Dark Luxury SaaS

---

## DESIGN PHILOSOPHY

**Vibe:** Premium tool voor serieuze schoonmaakbedrijven. Niet corporate, niet saai.
Denk: Vercel × Linear × Raycast maar dan voor de schoonmaakbranche.

**Kernwoorden:** Prestige. Efficiëntie. Macht. Moderniteit.

---

## KLEURENPALET

```
--black:      #000000
--deep:       #080810
--surface:    #0D0D1A
--border:     #1A1A2E
--accent:     #4F46E5  (elektrisch indigo)
--accent-2:   #06B6D4  (cyan)
--accent-3:   #8B5CF6  (paars)
--white:      #F8F8FF
--muted:      #6B7280
--glow:       rgba(79, 70, 229, 0.4)
```

**Gradient hero:** `radial-gradient(ellipse 80% 60% at 50% -20%, #1e1b4b, #000000)`

---

## TYPOGRAFIE

- **Headlines:** "Space Grotesk" (bold, condensed)
- **Body:** "Inter" (clean, modern)
- **Mono/accent:** "JetBrains Mono" (voor stats/numbers/codes)

---

## UNIEKE UI ELEMENTEN

### 1. Custom Cursor
- Kleine witte dot (8px) + grote cirkel outline (40px) volgt met lag
- Op hover over buttons: cursor explodeert naar 80px en vult zich met accent kleur
- Op hover over tekst: cursor vervormt naar text-cursor maar dan glowing

### 2. Hero Sectie — "MORPHING HEADLINE"
```
Volledige viewport height
Achtergrond: animated gradient mesh (CSS houdini paint worklet)

Headline morpht continu:
"Bereken elke offerte in 30 seconden"
→ letters vallen uiteen → hervormen naar →
"Stop met geld verliezen op elk project"
→ → "Jouw concurrenten gebruiken dit al"

Onderin: floating glassmorphism card met LIVE demo calculator
(mensen kunnen direct typen, zien het product in actie)
```

### 3. "TUNNEL" Scroll Sectie
- Bij scrollen: gebruiker vliegt door een 3D tunnel van features
- CSS perspective + translateZ animatie
- Elke feature "vliegt" voorbij als je scrollt
- Effect: alsof je door het product heen vliegt

### 4. Bento Grid Features
Asymmetrische grid (niet uniform):
```
┌──────────────┬──────┬──────┐
│              │      │      │
│  BIG CARD    │ sm   │ sm   │
│  (full gif)  │      │      │
│              ├──────┴──────┤
│              │  WIDE CARD  │
└──────────────┴─────────────┘
```
- Kaarten hebben glow border on hover
- Sommige kaarten hebben live animaties/demo's

### 5. Holographic Pricing Cards
- Achtergrond: CSS holografisch effect (linear-gradient rotating)
- On hover: card kantelt in 3D (CSS perspective)
- "Most Popular" badge met animated border (conic-gradient spinning)
- Prijs telt op van 0 naar de echte prijs bij scroll into view

### 6. Noise Texture Overlay
- Subtiele grain/noise over hele site
- Geeft het een premium printed/matte gevoel
- SVG filter feTurbulence

### 7. Stats Sectie — Glassmorphism
- Zwevende cards met backdrop-blur
- Nummers counten omhoog bij scroll
- Glow effect onder elke card

### 8. Testimonials — Infinite Marquee
- 2 rijen, tegengestelde richting
- Kaarten met foto, naam, sterren
- Pauzeren bij hover

### 9. "HOW IT WORKS" — Interactive Timeline
- Verticale lijn die optrekt bij scroll
- Elke stap "unlocks" als de lijn er langs komt
- Icon animaties per stap

### 10. CTA Sectie — Gradient Explosion
- Pure gradient achtergrond (indigo → cyan → paars)
- Grote bold tekst
- Button met shimmer effect (lichtstraal beweegt over button)

---

## PAGINA STRUCTUUR

### / (Homepage)
1. **Nav** — Logo links, links midden, CTA rechts (sticky, blur on scroll)
2. **Hero** — Morphing headline + live demo card + scroll indicator
3. **Logos/Social proof** — "Vertrouwd door X bedrijven" marquee
4. **Tunnel scroll** — Features fly-by
5. **Bento grid** — Feature details
6. **Stats** — Glassmorphism counters
7. **Testimonials** — Infinite marquee
8. **Pricing** — Holographic cards
9. **FAQ** — Accordion met smooth animation
10. **CTA** — Gradient explosion
11. **Footer** — Dark, minimal, links

### /prijzen
- Uitgebreide vergelijkingstabel
- Toggle maandelijks/jaarlijks (met animatie)
- Feature checklist per tier

### /demo
- Fullscreen calculator embed
- Uitleg stappen links, live preview rechts

### /licentie-activeren
- Simpele clean pagina
- Input veld voor license key
- Direct feedback of key geldig is

---

## ANIMATIES (GSAP)

```js
// Scroll-triggered tekst reveals
gsap.from(".hero-title .word", {
  y: 100, opacity: 0, stagger: 0.1,
  scrollTrigger: { trigger: ".hero", start: "top 80%" }
})

// Magnetic buttons
document.querySelectorAll('.btn-magnetic').forEach(btn => {
  btn.addEventListener('mousemove', (e) => {
    const rect = btn.getBoundingClientRect()
    const x = e.clientX - rect.left - rect.width / 2
    const y = e.clientY - rect.top - rect.height / 2
    btn.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px)`
  })
})

// Number counter
gsap.to(counter, {
  innerText: targetNumber,
  duration: 2,
  snap: { innerText: 1 },
  scrollTrigger: { trigger: counter, start: "top 80%" }
})
```

---

## TECHNISCHE STACK

- **WordPress** custom theme (geen page builder)
- **PHP 8.3** templates
- **Vanilla CSS** (geen Bootstrap/Tailwind — alles custom)
- **GSAP 3** + ScrollTrigger + TextPlugin
- **Three.js** (optioneel: hero particle field)
- **Google Fonts** (Space Grotesk + Inter + JetBrains Mono)

---

## VPS STRUCTUUR (na installatie)

```
/var/www/html/           → WordPress (CleanMasterzz website)
/var/www/licenses/       → License Server API
/var/www/html/wp-content/themes/cleanmasterzz/  → Custom theme
/var/www/html/wp-content/plugins/cleanmasterzz-calculator/  → De plugin
/backups/                → Automatische backups
```

---

## INSTALLATIE VOLGORDE (Nacht van 24 → 25 maart, 02:00)

1. [ ] VPS install script uitvoeren (LEMP + WordPress)
2. [ ] License server deployen
3. [ ] WordPress basis configureren
4. [ ] Custom theme aanmaken + activeren
5. [ ] Homepage bouwen (alle secties)
6. [ ] Prijzen pagina
7. [ ] Demo pagina
8. [ ] Plugin uploaden naar WordPress
9. [ ] Plugin + License server koppelen
10. [ ] Testen alles

---

## TODO PLUGIN (ook vannacht)

- [ ] class-cmcalc-boss-portal.php
- [ ] tab-analytics.php (Pro)
- [ ] class-cmcalc-pdf.php (Pro)
- [ ] Bedrijf wizard modal
- [ ] Hoofdplugin updaten

