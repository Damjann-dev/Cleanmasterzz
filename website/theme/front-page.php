<?php get_header(); ?>

<!-- ═══════════════════════════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════════════════════════ -->
<section class="cm-hero" id="hero">
    <!-- Animated gradient mesh background -->
    <div class="cm-hero-bg" aria-hidden="true">
        <div class="cm-mesh cm-mesh--1"></div>
        <div class="cm-mesh cm-mesh--2"></div>
        <div class="cm-mesh cm-mesh--3"></div>
    </div>

    <!-- Grid lines -->
    <div class="cm-grid-lines" aria-hidden="true"></div>

    <div class="cm-container cm-hero-inner">

        <!-- Badge -->
        <div class="cm-hero-badge" data-aos="fade-down">
            <span class="cm-badge-dot"></span>
            <span>Nieuw — Boss Klantportaal nu beschikbaar</span>
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7h8M7 3l4 4-4 4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </div>

        <!-- Morphing headline -->
        <h1 class="cm-hero-title">
            <span class="cm-hero-line cm-hero-line--1">Bereken elke offerte</span>
            <span class="cm-hero-line cm-hero-line--2">
                <span class="cm-hero-morph" id="cmMorph">in 30 seconden</span>
            </span>
        </h1>

        <p class="cm-hero-sub" id="cmHeroSub">
            De professionele WordPress plugin voor schoonmaakbedrijven.<br>
            Automatische offertes, boekingsbeheer, analytics en klantportaal.
        </p>

        <div class="cm-hero-actions">
            <a href="<?php echo esc_url(home_url('/prijzen')); ?>" class="cm-btn cm-btn--primary cm-btn--lg cm-btn--magnetic">
                <span>Start gratis</span>
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M4 9h10M9 4l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a href="<?php echo esc_url(home_url('/demo')); ?>" class="cm-btn cm-btn--ghost cm-btn--lg">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><circle cx="9" cy="9" r="7.5" stroke="currentColor" stroke-width="1.2"/><path d="M7 6.5l5 2.5-5 2.5V6.5z" fill="currentColor"/></svg>
                <span>Live demo bekijken</span>
            </a>
        </div>

        <!-- Stats strip -->
        <div class="cm-hero-stats">
            <div class="cm-hero-stat">
                <span class="cm-hero-stat-num" data-count="500">0</span><span>+</span>
                <span class="cm-hero-stat-label">Bedrijven</span>
            </div>
            <div class="cm-hero-stat-divider"></div>
            <div class="cm-hero-stat">
                <span class="cm-hero-stat-num" data-count="12000">0</span><span>+</span>
                <span class="cm-hero-stat-label">Offertes verstuurd</span>
            </div>
            <div class="cm-hero-stat-divider"></div>
            <div class="cm-hero-stat">
                <span class="cm-hero-stat-num" data-count="30">0</span><span>s</span>
                <span class="cm-hero-stat-label">Gem. berekeningtijd</span>
            </div>
            <div class="cm-hero-stat-divider"></div>
            <div class="cm-hero-stat">
                <span class="cm-hero-stat-num" data-count="98">0</span><span>%</span>
                <span class="cm-hero-stat-label">Klanttevredenheid</span>
            </div>
        </div>

    </div>

    <!-- Hero product preview card (floating) -->
    <div class="cm-hero-preview" id="cmHeroPreview">
        <div class="cm-preview-card">
            <div class="cm-preview-header">
                <div class="cm-preview-dots">
                    <span></span><span></span><span></span>
                </div>
                <span class="cm-preview-title">CleanMasterzz Calculator</span>
            </div>
            <div class="cm-preview-body">
                <div class="cm-preview-row">
                    <span>Dienst</span>
                    <span class="cm-preview-val">Reguliere schoonmaak</span>
                </div>
                <div class="cm-preview-row">
                    <span>Oppervlak</span>
                    <span class="cm-preview-val cm-preview-val--typing" id="cmTypingArea">85</span><span class="cm-preview-val"> m²</span>
                </div>
                <div class="cm-preview-row">
                    <span>Frequentie</span>
                    <span class="cm-preview-val">Wekelijks</span>
                </div>
                <div class="cm-preview-divider"></div>
                <div class="cm-preview-row cm-preview-row--total">
                    <span>Totaal per beurt</span>
                    <span class="cm-preview-price" id="cmPreviewPrice">&euro;187,50</span>
                </div>
                <a href="<?php echo esc_url(home_url('/demo')); ?>" class="cm-preview-btn">Offerte versturen →</a>
            </div>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="cm-scroll-indicator" aria-hidden="true">
        <div class="cm-scroll-line"></div>
        <span>Scroll</span>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     SOCIAL PROOF MARQUEE
═══════════════════════════════════════════════════════════════════════════ -->
<section class="cm-marquee-section">
    <p class="cm-marquee-label">Vertrouwd door schoonmaakbedrijven door heel Nederland</p>
    <div class="cm-marquee-track">
        <div class="cm-marquee-inner" id="cmMarquee">
            <?php
            $companies = array('ProClean BV','Sparkle Services','GlanzMeister','TopSchoon','HollandClean','PoetsExpert','BrightOffice','SchoonMakers NL','CleanProfs','PoetsTeam');
            foreach ($companies as $c) :
            ?>
            <span class="cm-marquee-item"><?php echo esc_html($c); ?></span>
            <?php endforeach; ?>
            <?php foreach ($companies as $c) : ?>
            <span class="cm-marquee-item"><?php echo esc_html($c); ?></span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     TUNNEL FEATURES (3D SCROLL EFFECT)
═══════════════════════════════════════════════════════════════════════════ -->
<section class="cm-tunnel-section" id="features">
    <div class="cm-container">
        <div class="cm-section-header">
            <div class="cm-tag">Features</div>
            <h2 class="cm-section-title">Alles wat uw bedrijf nodig heeft</h2>
            <p class="cm-section-sub">Één plugin. Alles erin. Geen gedoe.</p>
        </div>
    </div>

    <div class="cm-tunnel" id="cmTunnel">
        <div class="cm-tunnel-inner">

            <?php
            $features = array(
                array(
                    'icon' => '⚡',
                    'title' => 'Offerte in 30 seconden',
                    'desc' => 'Klant vult oppervlak en dienst in — de calculator berekent direct de exacte prijs. Geen gedoe, geen mailing. Gewoon: klik, prijs, offerte.',
                    'color' => '#6366f1',
                    'tag' => 'Free',
                ),
                array(
                    'icon' => '📊',
                    'title' => 'Analytics Dashboard',
                    'desc' => 'Zie precies welke diensten het meest worden geboekt, wat uw omzet per periode is en waar uw klanten vandaan komen. Allemaal in één overzicht.',
                    'color' => '#06b6d4',
                    'tag' => 'Pro',
                ),
                array(
                    'icon' => '👑',
                    'title' => 'Boss Klantportaal',
                    'desc' => 'Uw klanten loggen in op hun eigen portaal. Boekingen bekijken, berichten sturen, facturen downloaden. Premium service zonder extra werk.',
                    'color' => '#f59e0b',
                    'tag' => 'Boss',
                ),
                array(
                    'icon' => '📄',
                    'title' => 'PDF Facturen',
                    'desc' => 'Automatisch gegenereerde facturen met uw bedrijfslogo, BTW-nummer en IBAN. Direct te downloaden of versturen per e-mail.',
                    'color' => '#10b981',
                    'tag' => 'Pro',
                ),
                array(
                    'icon' => '🗺️',
                    'title' => 'Werkgebieden & BTW',
                    'desc' => 'Stel per werkgebied verschillende tarieven en BTW-percentages in. Voorrijkosten automatisch berekend op basis van postcode.',
                    'color' => '#8b5cf6',
                    'tag' => 'Free',
                ),
            );
            foreach ($features as $i => $f) :
            ?>
            <div class="cm-tunnel-slide" data-index="<?php echo $i; ?>" style="--accent:<?php echo $f['color']; ?>">
                <div class="cm-tunnel-slide-inner">
                    <div class="cm-tunnel-icon"><?php echo $f['icon']; ?></div>
                    <div class="cm-tunnel-tag" style="background:<?php echo $f['color']; ?>22;color:<?php echo $f['color']; ?>"><?php echo esc_html($f['tag']); ?></div>
                    <h3><?php echo esc_html($f['title']); ?></h3>
                    <p><?php echo esc_html($f['desc']); ?></p>
                </div>
                <div class="cm-tunnel-glow" style="background:radial-gradient(circle at 50% 50%, <?php echo $f['color']; ?>20, transparent 70%)"></div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     BENTO GRID
═══════════════════════════════════════════════════════════════════════════ -->
<section class="cm-bento-section">
    <div class="cm-container">
        <div class="cm-section-header">
            <div class="cm-tag">Detail</div>
            <h2 class="cm-section-title">Gebouwd voor de praktijk</h2>
        </div>

        <div class="cm-bento-grid">

            <!-- Big card: calculator demo -->
            <div class="cm-bento-card cm-bento-card--big cm-bento-card--glow" data-glow-color="indigo">
                <div class="cm-bento-label">Live Calculator</div>
                <h3>Klanten zien de prijs terwijl ze typen</h3>
                <p>Geen wachten op een offerte-mail. Realtime prijsberekening zorgt voor hogere conversie.</p>
                <div class="cm-bento-demo">
                    <div class="cm-mini-calc">
                        <div class="cm-mini-calc-row">
                            <label>Oppervlak (m²)</label>
                            <input type="range" min="20" max="300" value="85" class="cm-mini-slider" id="cmBentoSlider">
                            <span class="cm-mini-value" id="cmBentoVal">85 m²</span>
                        </div>
                        <div class="cm-mini-calc-price">
                            <span>Prijs</span>
                            <span class="cm-mini-price" id="cmBentoPrice">&euro;187,50</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stat card: tijd -->
            <div class="cm-bento-card cm-bento-card--sm cm-bento-card--glow" data-glow-color="cyan">
                <div class="cm-bento-stat-big">30<span>s</span></div>
                <p>Gemiddelde tijd voor een complete offerte</p>
            </div>

            <!-- Stat card: diensten -->
            <div class="cm-bento-card cm-bento-card--sm cm-bento-card--glow" data-glow-color="purple">
                <div class="cm-bento-stat-big">∞</div>
                <p>Onbeperkt diensten, werkgebieden en sub-opties</p>
            </div>

            <!-- Wide card: multi-dienst -->
            <div class="cm-bento-card cm-bento-card--wide cm-bento-card--glow" data-glow-color="green">
                <div class="cm-bento-label">Multi-dienst</div>
                <h3>Meerdere diensten tegelijk</h3>
                <p>Klanten kunnen meerdere diensten combineren in één offerte. Prijzen worden automatisch opgeteld.</p>
                <div class="cm-bento-pills">
                    <span class="cm-pill" style="--c:#6366f1">Reguliere schoonmaak</span>
                    <span class="cm-pill" style="--c:#06b6d4">Ramen lappen</span>
                    <span class="cm-pill cm-pill--add">+ Dienst toevoegen</span>
                </div>
            </div>

            <!-- Card: email -->
            <div class="cm-bento-card cm-bento-card--sm cm-bento-card--glow" data-glow-color="amber">
                <div class="cm-bento-icon">📧</div>
                <h4>Automatische e-mails</h4>
                <p>Bevestiging, herinnering en factuur — allemaal automatisch.</p>
            </div>

            <!-- Card: responsive -->
            <div class="cm-bento-card cm-bento-card--sm">
                <div class="cm-bento-icon">📱</div>
                <h4>100% Responsive</h4>
                <p>Perfect op mobiel, tablet en desktop.</p>
            </div>

            <!-- Card: WP compatible -->
            <div class="cm-bento-card cm-bento-card--sm">
                <div class="cm-bento-icon">🔌</div>
                <h4>WordPress Plugin</h4>
                <p>Installeer met 1 klik. Werkt met elk thema.</p>
            </div>

        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     STATS GLASSMORPHISM
═══════════════════════════════════════════════════════════════════════════ -->
<section class="cm-stats-section">
    <div class="cm-stats-bg" aria-hidden="true">
        <div class="cm-mesh cm-mesh--4"></div>
    </div>
    <div class="cm-container">
        <div class="cm-stats-grid">
            <?php
            $stats = array(
                array( 'num' => '€ 2.4M+', 'label' => 'Aan offertes verwerkt', 'icon' => '💶' ),
                array( 'num' => '500+',    'label' => 'Actieve bedrijven',      'icon' => '🏢' ),
                array( 'num' => '4.9/5',   'label' => 'Gemiddelde beoordeling', 'icon' => '⭐' ),
                array( 'num' => '< 2 min', 'label' => 'Gemiddelde support responstijd', 'icon' => '⚡' ),
            );
            foreach ($stats as $s) :
            ?>
            <div class="cm-stat-glass">
                <div class="cm-stat-glass-icon"><?php echo $s['icon']; ?></div>
                <div class="cm-stat-glass-num"><?php echo esc_html($s['num']); ?></div>
                <div class="cm-stat-glass-label"><?php echo esc_html($s['label']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     TESTIMONIALS MARQUEE
═══════════════════════════════════════════════════════════════════════════ -->
<section class="cm-testimonials-section" id="reviews">
    <div class="cm-container">
        <div class="cm-section-header">
            <div class="cm-tag">Reviews</div>
            <h2 class="cm-section-title">Wat klanten zeggen</h2>
        </div>
    </div>

    <?php
    $testimonials = array(
        array( 'name' => 'Mark de Vries', 'company' => 'ProClean BV', 'text' => 'Eindelijk een tool die écht werkt voor onze branche. In 3 minuten opgezet, onze offerte-tijd gehalveerd.', 'rating' => 5 ),
        array( 'name' => 'Sandra Bakker', 'company' => 'Sparkle Services', 'text' => 'De analytics geven me precies het inzicht dat ik nodig heb. Weet nu precies welke diensten het meest opleveren.', 'rating' => 5 ),
        array( 'name' => 'Jeroen Smit', 'company' => 'GlanzMeister', 'text' => 'Het klantportaal is een gamechanger. Klanten kunnen zelf hun boekingen bekijken, scheelt me tientallen mails per week.', 'rating' => 5 ),
        array( 'name' => 'Lisa van Dam', 'company' => 'TopSchoon', 'text' => 'PDF facturen direct na de boeking, BTW automatisch berekend. Dit had ik jaren geleden al moeten hebben.', 'rating' => 5 ),
        array( 'name' => 'Peter Janssen', 'company' => 'HollandClean', 'text' => 'Setup wizard had alles in 10 minuten geconfigureerd. Onze klanten vinden de calculator super gebruiksvriendelijk.', 'rating' => 5 ),
        array( 'name' => 'Fatima El Amrani', 'company' => 'BrightOffice', 'text' => 'De werkgebied-instelling met voorrijkosten per postcode is geniaal. Eindelijk eerlijke prijzen per locatie.', 'rating' => 5 ),
    );
    ?>

    <!-- Row 1: links naar rechts -->
    <div class="cm-testimonials-track cm-testimonials-track--1">
        <div class="cm-testimonials-inner" id="cmTestimonials1">
            <?php foreach (array_merge($testimonials, $testimonials) as $t) : ?>
            <div class="cm-testimonial-card">
                <div class="cm-testimonial-stars">
                    <?php echo str_repeat('★', $t['rating']); ?>
                </div>
                <p class="cm-testimonial-text">"<?php echo esc_html($t['text']); ?>"</p>
                <div class="cm-testimonial-author">
                    <div class="cm-testimonial-avatar"><?php echo strtoupper(substr($t['name'],0,1)); ?></div>
                    <div>
                        <div class="cm-testimonial-name"><?php echo esc_html($t['name']); ?></div>
                        <div class="cm-testimonial-company"><?php echo esc_html($t['company']); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Row 2: rechts naar links -->
    <div class="cm-testimonials-track cm-testimonials-track--2">
        <div class="cm-testimonials-inner" id="cmTestimonials2">
            <?php foreach (array_merge(array_reverse($testimonials), array_reverse($testimonials)) as $t) : ?>
            <div class="cm-testimonial-card">
                <div class="cm-testimonial-stars">
                    <?php echo str_repeat('★', $t['rating']); ?>
                </div>
                <p class="cm-testimonial-text">"<?php echo esc_html($t['text']); ?>"</p>
                <div class="cm-testimonial-author">
                    <div class="cm-testimonial-avatar"><?php echo strtoupper(substr($t['name'],0,1)); ?></div>
                    <div>
                        <div class="cm-testimonial-name"><?php echo esc_html($t['name']); ?></div>
                        <div class="cm-testimonial-company"><?php echo esc_html($t['company']); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     PRICING (HOLOGRAPHIC)
═══════════════════════════════════════════════════════════════════════════ -->
<section class="cm-pricing-section" id="pricing">
    <div class="cm-container">
        <div class="cm-section-header">
            <div class="cm-tag">Prijzen</div>
            <h2 class="cm-section-title">Kies uw plan</h2>
            <p class="cm-section-sub">Start gratis. Upgrade wanneer uw bedrijf groeit.</p>
        </div>

        <div class="cm-pricing-grid">

            <?php
            $plans = array(
                array(
                    'name'    => 'Free',
                    'price'   => '0',
                    'period'  => 'voor altijd',
                    'color'   => '#64748b',
                    'popular' => false,
                    'features'=> array(
                        'Prijscalculator',
                        'Boekingsbeheer',
                        'E-mail notificaties',
                        'Basis klantportaal',
                        'Kortingscodes',
                        'Enkelvoudig bedrijfsprofiel',
                    ),
                    'cta' => 'Download gratis',
                    'href' => '#',
                ),
                array(
                    'name'    => 'Pro',
                    'price'   => '29',
                    'period'  => 'per maand',
                    'color'   => '#6366f1',
                    'popular' => true,
                    'features'=> array(
                        'Alles van Free',
                        'Analytics dashboard',
                        'PDF facturen',
                        'Bedrijf setup wizard',
                        'Geavanceerde kortingen',
                        'Kalender & beschikbaarheid',
                        'Multi-bedrijf beheer',
                    ),
                    'cta' => 'Start Pro',
                    'href' => home_url('/prijzen'),
                ),
                array(
                    'name'    => 'Boss',
                    'price'   => '59',
                    'period'  => 'per maand',
                    'color'   => '#f59e0b',
                    'popular' => false,
                    'features'=> array(
                        'Alles van Pro',
                        'Boss klantportaal',
                        'Klantaccounts & login',
                        'Berichtensysteem',
                        'SMS notificaties',
                        'White-label (eigen branding)',
                        'Priority support',
                    ),
                    'cta' => 'Word Boss',
                    'href' => home_url('/prijzen'),
                ),
                array(
                    'name'    => 'Agency',
                    'price'   => '149',
                    'period'  => 'per maand',
                    'color'   => '#10b981',
                    'popular' => false,
                    'features'=> array(
                        'Alles van Boss',
                        'Onbeperkte bedrijven',
                        'Reseller rechten',
                        'API toegang',
                        'Dedicated support',
                        'SLA garantie',
                        'Custom integraties',
                    ),
                    'cta' => 'Contact opnemen',
                    'href' => home_url('/contact'),
                ),
            );

            foreach ($plans as $plan) :
            ?>
            <div class="cm-pricing-card <?php echo $plan['popular'] ? 'cm-pricing-card--popular' : ''; ?>"
                 style="--plan-color:<?php echo $plan['color']; ?>">

                <?php if ($plan['popular']) : ?>
                <div class="cm-pricing-badge">Meest gekozen</div>
                <?php endif; ?>

                <div class="cm-pricing-card-top">
                    <div class="cm-pricing-name"><?php echo esc_html($plan['name']); ?></div>
                    <div class="cm-pricing-price">
                        <span class="cm-pricing-currency">&euro;</span>
                        <span class="cm-pricing-amount"><?php echo esc_html($plan['price']); ?></span>
                    </div>
                    <div class="cm-pricing-period"><?php echo esc_html($plan['period']); ?></div>
                </div>

                <ul class="cm-pricing-features">
                    <?php foreach ($plan['features'] as $feat) : ?>
                    <li>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7.5" stroke="currentColor" stroke-opacity=".3"/><path d="M5 8l2 2 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <?php echo esc_html($feat); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <a href="<?php echo esc_url($plan['href']); ?>" class="cm-btn cm-pricing-btn <?php echo $plan['popular'] ? 'cm-btn--primary' : 'cm-btn--ghost'; ?> cm-btn--magnetic">
                    <span><?php echo esc_html($plan['cta']); ?></span>
                </a>

                <!-- Holographic overlay -->
                <div class="cm-pricing-holo" aria-hidden="true"></div>

            </div>
            <?php endforeach; ?>

        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     FAQ
═══════════════════════════════════════════════════════════════════════════ -->
<section class="cm-faq-section">
    <div class="cm-container cm-faq-inner">

        <div class="cm-faq-header">
            <div class="cm-tag">FAQ</div>
            <h2 class="cm-section-title">Veelgestelde vragen</h2>
        </div>

        <div class="cm-faq-list">
            <?php
            $faqs = array(
                array(
                    'q' => 'Hoe installeer ik de plugin?',
                    'a' => 'Download de plugin, ga naar WordPress → Plugins → Nieuwe plugin → Uploaden. Na activatie start automatisch de setup wizard die u in 10 stappen door alle instellingen leidt.',
                ),
                array(
                    'q' => 'Werkt het met mijn bestaande WordPress thema?',
                    'a' => 'Ja. De calculator wordt via een shortcode of block geplaatst en past zich aan aan uw stijl. U kunt kleuren, lettertypen en randius volledig aanpassen in het stijlpaneel.',
                ),
                array(
                    'q' => 'Kan ik meerdere diensten instellen?',
                    'a' => 'Absoluut. U kunt onbeperkt diensten aanmaken, elk met eigen prijsregels, sub-opties, BTW-tarieven en werkgebieden. Klanten kunnen meerdere diensten combineren.',
                ),
                array(
                    'q' => 'Hoe werkt de licentiesleutel?',
                    'a' => 'Na aankoop ontvangt u per e-mail een licentiesleutel. U vult deze in onder Instellingen → Licentie in uw WordPress dashboard. Het Pro/Boss/Agency abonnement wordt direct geactiveerd.',
                ),
                array(
                    'q' => 'Kan ik upgraden van Free naar Pro?',
                    'a' => 'Ja. Activeer een Pro licentie en alle Pro features zijn direct beschikbaar. Uw bestaande data en instellingen blijven intact.',
                ),
                array(
                    'q' => 'Is er een money-back garantie?',
                    'a' => '30 dagen niet-goed-geld-terug garantie. Geen vragen gesteld. Stuur een e-mail en u ontvangt het volledige bedrag terug.',
                ),
            );
            foreach ($faqs as $i => $faq) :
            ?>
            <div class="cm-faq-item" id="faq-<?php echo $i; ?>">
                <button class="cm-faq-q" aria-expanded="false" aria-controls="faq-a-<?php echo $i; ?>">
                    <span><?php echo esc_html($faq['q']); ?></span>
                    <svg class="cm-faq-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M5 7.5l5 5 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
                <div class="cm-faq-a" id="faq-a-<?php echo $i; ?>" hidden>
                    <p><?php echo esc_html($faq['a']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════════════════════════
     CTA GRADIENT EXPLOSION
═══════════════════════════════════════════════════════════════════════════ -->
<section class="cm-cta-section">
    <div class="cm-cta-bg" aria-hidden="true">
        <div class="cm-cta-orb cm-cta-orb--1"></div>
        <div class="cm-cta-orb cm-cta-orb--2"></div>
        <div class="cm-cta-orb cm-cta-orb--3"></div>
    </div>
    <div class="cm-noise" aria-hidden="true"></div>
    <div class="cm-container cm-cta-inner">
        <div class="cm-cta-tag">Klaar om te beginnen?</div>
        <h2 class="cm-cta-title">Stop met tijd verliezen.<br>Begin vandaag.</h2>
        <p class="cm-cta-sub">Gratis installeren. Geen creditcard vereist. In 10 minuten live.</p>
        <div class="cm-cta-actions">
            <a href="<?php echo esc_url(home_url('/prijzen')); ?>" class="cm-btn cm-btn--white cm-btn--lg cm-btn--magnetic">
                <span>Start gratis</span>
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M4 9h10M9 4l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <a href="<?php echo esc_url(home_url('/demo')); ?>" class="cm-btn cm-btn--ghost-white cm-btn--lg">
                Demo bekijken
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>
