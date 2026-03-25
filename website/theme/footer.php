</main>

<!-- Footer -->
<footer class="cm-footer">
    <div class="cm-noise" aria-hidden="true"></div>

    <div class="cm-container">

        <!-- Top row -->
        <div class="cm-footer-top">
            <div class="cm-footer-brand">
                <a href="<?php echo esc_url( home_url('/') ); ?>" class="cm-logo">
                    <span class="cm-logo-text">
                        <span class="cm-logo-clean">Clean</span><span class="cm-logo-masterzz">masterzz</span>
                    </span>
                </a>
                <p class="cm-footer-tagline">Professionele offertesoftware<br>voor schoonmaakbedrijven.</p>
                <div class="cm-footer-social">
                    <a href="#" aria-label="LinkedIn" class="cm-social-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                    </a>
                    <a href="#" aria-label="Instagram" class="cm-social-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                    </a>
                </div>
            </div>

            <div class="cm-footer-links-grid">
                <div class="cm-footer-col">
                    <h4>Product</h4>
                    <ul>
                        <li><a href="<?php echo esc_url(home_url('/demo')); ?>">Live demo</a></li>
                        <li><a href="<?php echo esc_url(home_url('/prijzen')); ?>">Prijzen</a></li>
                        <li><a href="<?php echo esc_url(home_url('/changelog')); ?>">Changelog</a></li>
                        <li><a href="<?php echo esc_url(home_url('/docs')); ?>">Documentatie</a></li>
                    </ul>
                </div>
                <div class="cm-footer-col">
                    <h4>Features</h4>
                    <ul>
                        <li><a href="#calculator">Calculator</a></li>
                        <li><a href="#analytics">Analytics</a></li>
                        <li><a href="#portal">Klantportaal</a></li>
                        <li><a href="#pdf">PDF Facturen</a></li>
                    </ul>
                </div>
                <div class="cm-footer-col">
                    <h4>Bedrijf</h4>
                    <ul>
                        <li><a href="<?php echo esc_url(home_url('/over')); ?>">Over ons</a></li>
                        <li><a href="<?php echo esc_url(home_url('/contact')); ?>">Contact</a></li>
                        <li><a href="<?php echo esc_url(home_url('/licentie')); ?>">Licentie activeren</a></li>
                        <li><a href="<?php echo esc_url(home_url('/klantportaal')); ?>">Klantportaal</a></li>
                    </ul>
                </div>
                <div class="cm-footer-col">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="<?php echo esc_url(home_url('/privacy')); ?>">Privacybeleid</a></li>
                        <li><a href="<?php echo esc_url(home_url('/voorwaarden')); ?>">Algemene voorwaarden</a></li>
                        <li><a href="<?php echo esc_url(home_url('/cookies')); ?>">Cookiebeleid</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="cm-footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> CleanMasterzz. Alle rechten voorbehouden.</p>
            <p class="cm-footer-made">Gebouwd voor schoonmaakprofessionals 🧹</p>
        </div>

    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
