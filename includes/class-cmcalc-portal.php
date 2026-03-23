<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * CMCalc_Portal
 * Klantportaal via shortcode [cmcalc_portal].
 *
 * Flow:
 *  1. Bij aanmaken boeking → uniek token opslaan (_cm_booking_token)
 *  2. Token-link in bevestigingsmail
 *  3. Klant opent link → shortcode toont boekingsdetails + tijdlijn
 *  4. Fallback: klant kan email invoeren → magische link per mail ontvangen
 */
class CMCalc_Portal {

    public static function register() {
        add_shortcode( 'cmcalc_portal', array( __CLASS__, 'render' ) );
        add_action( 'wp_ajax_nopriv_cmcalc_portal_send_link', array( __CLASS__, 'handle_send_link' ) );
        add_action( 'wp_ajax_cmcalc_portal_send_link', array( __CLASS__, 'handle_send_link' ) );
    }

    /**
     * Shortcode renderer.
     */
    public static function render( $atts ) {
        $styles   = CMCalc_Admin::get_styles();
        $primary  = $styles['primary_color'] ?? '#1B2A4A';
        $accent   = $styles['accent_color']  ?? '#4DA8DA';
        $radius   = intval( $styles['border_radius'] ?? 16 );

        $token = sanitize_text_field( $_GET['booking_ref'] ?? '' );

        if ( $token ) {
            return self::render_booking_view( $token, $primary, $accent, $radius );
        }

        return self::render_lookup_form( $primary, $accent, $radius );
    }

    // ─── Boekingsoverzicht ────────────────────────────────────────────────────

    private static function render_booking_view( $token, $primary, $accent, $radius ) {
        $bookings = get_posts( array(
            'post_type'      => 'boeking',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array( 'key' => '_cm_booking_token', 'value' => sanitize_text_field( $token ) ),
            ),
        ) );

        if ( empty( $bookings ) ) {
            return self::error_box( 'Boekingslink niet geldig. Controleer of u de volledige link heeft geopend, of vraag een nieuwe link aan.', $radius );
        }

        $b  = $bookings[0];
        $id = $b->ID;

        $name             = get_post_meta( $id, '_cm_booking_name', true );
        $email            = get_post_meta( $id, '_cm_booking_email', true );
        $phone            = get_post_meta( $id, '_cm_booking_phone', true );
        $address          = get_post_meta( $id, '_cm_booking_address', true );
        $date             = get_post_meta( $id, '_cm_booking_date', true );
        $total            = floatval( get_post_meta( $id, '_cm_booking_total', true ) );
        $message          = get_post_meta( $id, '_cm_booking_message', true );
        $status           = get_post_meta( $id, '_cm_booking_status', true ) ?: 'nieuw';
        $services_json    = get_post_meta( $id, '_cm_booking_services', true );
        $postcode         = get_post_meta( $id, '_cm_booking_postcode', true );
        $house_number     = get_post_meta( $id, '_cm_booking_house_number', true );
        $travel_surcharge = floatval( get_post_meta( $id, '_cm_booking_travel_surcharge', true ) );
        $discount_code    = get_post_meta( $id, '_cm_discount_code', true );
        $created          = get_the_date( 'd-m-Y H:i', $id );

        $services_data = ! empty( $services_json ) ? json_decode( $services_json, true ) : array();

        $status_config = array(
            'nieuw'       => array( 'label' => 'Ontvangen',   'color' => '#3b82f6', 'icon' => '📥' ),
            'bevestigd'   => array( 'label' => 'Bevestigd',   'color' => '#8b5cf6', 'icon' => '✅' ),
            'gepland'     => array( 'label' => 'Ingepland',   'color' => '#f59e0b', 'icon' => '📅' ),
            'voltooid'    => array( 'label' => 'Voltooid',    'color' => '#10b981', 'icon' => '🎉' ),
            'geannuleerd' => array( 'label' => 'Geannuleerd', 'color' => '#ef4444', 'icon' => '❌' ),
        );
        $sc = $status_config[ $status ] ?? array( 'label' => ucfirst( $status ), 'color' => '#6c757d', 'icon' => '•' );

        $timeline_steps = array( 'nieuw', 'bevestigd', 'gepland', 'voltooid' );
        $status_order   = array_flip( $timeline_steps );
        $current_order  = $status_order[ $status ] ?? 0;

        ob_start();
        ?>
        <div class="cmcalc-portal" style="--cm-primary:<?php echo esc_attr( $primary ); ?>;--cm-accent:<?php echo esc_attr( $accent ); ?>;--cm-radius:<?php echo intval( $radius ); ?>px;">

            <!-- Header -->
            <div class="cmp-header">
                <div class="cmp-status-badge" style="background:<?php echo esc_attr( $sc['color'] ); ?>20;color:<?php echo esc_attr( $sc['color'] ); ?>;border:1px solid <?php echo esc_attr( $sc['color'] ); ?>40;">
                    <?php echo $sc['icon']; ?> <?php echo esc_html( $sc['label'] ); ?>
                </div>
                <h2 class="cmp-title">Uw boeking</h2>
                <p class="cmp-meta">Aangemaakt op <?php echo esc_html( $created ); ?> &bull; Ref: <code>#<?php echo intval( $id ); ?></code></p>
            </div>

            <?php if ( $status !== 'geannuleerd' ) : ?>
            <!-- Tijdlijn -->
            <div class="cmp-timeline">
                <?php foreach ( $timeline_steps as $step ) :
                    $scfg   = $status_config[ $step ] ?? array( 'label' => $step, 'color' => '#6c757d', 'icon' => '•' );
                    $s_ord  = $status_order[ $step ] ?? 0;
                    $done   = $s_ord < $current_order;
                    $active = $s_ord === $current_order;
                    $cls    = $done ? 'cmp-step--done' : ( $active ? 'cmp-step--active' : 'cmp-step--pending' );
                ?>
                <div class="cmp-step <?php echo esc_attr( $cls ); ?>">
                    <div class="cmp-step-dot" style="<?php echo $active ? 'background:' . esc_attr( $sc['color'] ) . ';border-color:' . esc_attr( $sc['color'] ) . ';' : ( $done ? 'background:' . esc_attr( $primary ) . ';border-color:' . esc_attr( $primary ) . ';' : '' ); ?>">
                        <?php echo $done ? '✓' : esc_html( $scfg['icon'] ); ?>
                    </div>
                    <span class="cmp-step-label"><?php echo esc_html( $scfg['label'] ); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="cmp-grid">

                <!-- Diensten -->
                <div class="cmp-card">
                    <h3 class="cmp-card-title">Diensten</h3>
                    <table class="cmp-table">
                        <thead>
                            <tr>
                                <th>Dienst</th>
                                <th style="text-align:center;">Aantal</th>
                                <th style="text-align:right;">Bedrag</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $services_data ) ) : ?>
                                <?php foreach ( $services_data as $svc ) : ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( $svc['title'] ?? '' ); ?>
                                        <?php if ( ! empty( $svc['sub_options'] ) ) : ?>
                                            <div class="cmp-subopts">
                                                <?php foreach ( (array) $svc['sub_options'] as $so ) : ?>
                                                    <span>&rsaquo; <?php echo esc_html( $so ); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ( ! empty( $svc['requires_quote'] ) ) : ?>
                                            <em style="color:#999;">offerte</em>
                                        <?php else : ?>
                                            <?php echo floatval( $svc['quantity'] ); ?> <?php echo esc_html( $svc['unit'] ?? '' ); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;font-weight:600;">
                                        <?php if ( ! empty( $svc['requires_quote'] ) ) : ?>
                                            <em style="color:#999;">op maat</em>
                                        <?php else : ?>
                                            &euro;<?php echo number_format( floatval( $svc['line_total'] ?? 0 ), 2, ',', '.' ); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <?php if ( $travel_surcharge > 0 ) : ?>
                            <tr class="cmp-row-extra">
                                <td colspan="2" style="color:#6c757d;font-size:13px;">Voorrijkosten</td>
                                <td style="text-align:right;">&euro;<?php echo number_format( $travel_surcharge, 2, ',', '.' ); ?></td>
                            </tr>
                            <?php endif; ?>

                            <?php if ( $discount_code ) : ?>
                            <tr class="cmp-row-extra">
                                <td colspan="2" style="color:#10b981;font-size:13px;">Kortingscode <code><?php echo esc_html( $discount_code ); ?></code></td>
                                <td style="text-align:right;color:#10b981;">toegepast</td>
                            </tr>
                            <?php endif; ?>

                            <tr class="cmp-row-total">
                                <td colspan="2"><strong>Totaal</strong></td>
                                <td style="text-align:right;"><strong>&euro;<?php echo number_format( $total, 2, ',', '.' ); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Gegevens -->
                <div class="cmp-card">
                    <h3 class="cmp-card-title">Uw gegevens</h3>
                    <dl class="cmp-dl">
                        <dt>Naam</dt><dd><?php echo esc_html( $name ); ?></dd>
                        <dt>E-mail</dt><dd><?php echo esc_html( $email ); ?></dd>
                        <?php if ( $phone ) : ?><dt>Telefoon</dt><dd><?php echo esc_html( $phone ); ?></dd><?php endif; ?>
                        <?php if ( $address ) : ?><dt>Adres</dt><dd><?php echo esc_html( $address ); ?></dd><?php endif; ?>
                        <?php if ( $postcode ) : ?><dt>Postcode</dt><dd><?php echo esc_html( $postcode . ' ' . $house_number ); ?></dd><?php endif; ?>
                        <?php if ( $date ) : ?><dt>Voorkeursdatum</dt><dd><?php echo esc_html( $date ); ?></dd><?php endif; ?>
                    </dl>

                    <?php if ( $message ) : ?>
                    <div class="cmp-message-block">
                        <p class="cmp-message-label">Uw bericht</p>
                        <p class="cmp-message-text"><?php echo nl2br( esc_html( $message ) ); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>

            <div class="cmp-footer">
                <p>Vragen over uw boeking? Neem contact op via de contactpagina.</p>
            </div>

        </div>
        <?php self::render_portal_styles( $primary, $accent, $radius ); ?>
        <?php
        return ob_get_clean();
    }

    // ─── E-mail opzoekformulier ───────────────────────────────────────────────

    private static function render_lookup_form( $primary, $accent, $radius ) {
        $nonce = wp_create_nonce( 'cmcalc_portal_nonce' );
        ob_start();
        ?>
        <div class="cmcalc-portal" style="--cm-primary:<?php echo esc_attr( $primary ); ?>;--cm-accent:<?php echo esc_attr( $accent ); ?>;--cm-radius:<?php echo intval( $radius ); ?>px;">
            <div class="cmp-lookup-wrap">
                <div class="cmp-lookup-icon">📋</div>
                <h2 class="cmp-title">Uw boeking bekijken</h2>
                <p class="cmp-subtitle">Voer uw e-mailadres in. Als er een actieve boeking op staat, sturen we u een directe link toe.</p>

                <div id="cmp-lookup-form" class="cmp-form">
                    <div class="cmp-input-row">
                        <input type="email" id="cmp-email" placeholder="uw@emailadres.nl" autocomplete="email">
                        <button type="button" id="cmp-send-btn" onclick="cmcalcPortalSendLink('<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>','<?php echo esc_js( $nonce ); ?>')">
                            Link versturen
                        </button>
                    </div>
                    <p id="cmp-msg" class="cmp-msg" style="display:none;"></p>
                </div>
            </div>
        </div>

        <script>
        function cmcalcPortalSendLink(ajaxUrl, nonce) {
            var email = document.getElementById('cmp-email').value.trim();
            var msgEl = document.getElementById('cmp-msg');
            var btn   = document.getElementById('cmp-send-btn');

            if (!email || !email.includes('@')) {
                msgEl.textContent = 'Voer een geldig e-mailadres in.';
                msgEl.className = 'cmp-msg cmp-msg--error';
                msgEl.style.display = 'block';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Verzenden...';

            var fd = new FormData();
            fd.append('action', 'cmcalc_portal_send_link');
            fd.append('nonce',  nonce);
            fd.append('email',  email);

            fetch(ajaxUrl, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        msgEl.textContent = 'Als uw e-mailadres bij ons bekend is, heeft u zojuist een link ontvangen.';
                        msgEl.className = 'cmp-msg cmp-msg--success';
                        document.getElementById('cmp-lookup-form').style.display = 'none';
                    } else {
                        msgEl.textContent = data.data || 'Er is iets misgegaan. Probeer het opnieuw.';
                        msgEl.className = 'cmp-msg cmp-msg--error';
                        btn.disabled = false;
                        btn.textContent = 'Link versturen';
                    }
                    msgEl.style.display = 'block';
                })
                .catch(function() {
                    msgEl.textContent = 'Verbindingsfout. Probeer het later opnieuw.';
                    msgEl.className = 'cmp-msg cmp-msg--error';
                    msgEl.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Link versturen';
                });
        }
        </script>

        <?php self::render_portal_styles( $primary, $accent, $radius ); ?>
        <?php
        return ob_get_clean();
    }

    // ─── AJAX: Verstuur magische link ─────────────────────────────────────────

    public static function handle_send_link() {
        if ( ! check_ajax_referer( 'cmcalc_portal_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Ongeldige aanvraag' );
        }

        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( 'Ongeldig e-mailadres' );
        }

        // Rate limiting: max 3 verzoeken per uur per e-mail
        $rate_key = 'cmcalc_portal_rate_' . md5( $email );
        $attempts = intval( get_transient( $rate_key ) );
        if ( $attempts >= 3 ) {
            wp_send_json_success(); // Stille success (geen info lekken)
            return;
        }
        set_transient( $rate_key, $attempts + 1, HOUR_IN_SECONDS );

        // Zoek boekingen voor dit e-mailadres
        $bookings = get_posts( array(
            'post_type'      => 'boeking',
            'posts_per_page' => 5,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array( 'key' => '_cm_booking_email', 'value' => $email ),
            ),
            'orderby' => 'date',
            'order'   => 'DESC',
        ) );

        if ( ! empty( $bookings ) ) {
            self::send_magic_link_email( $email, $bookings );
        }

        // Altijd success teruggeven (privacy: niet onthullen of email bestaat)
        wp_send_json_success();
    }

    // ─── Magic link e-mail ────────────────────────────────────────────────────

    private static function send_magic_link_email( $email, $bookings ) {
        $settings    = CMCalc_Admin::get_settings();
        $styles      = CMCalc_Admin::get_styles();
        $primary     = $styles['primary_color'] ?? '#1B2A4A';
        $site_name   = get_bloginfo( 'name' );
        $portal_page = get_option( 'cmcalc_portal_page_id' );

        $subject = 'Uw boekingen bij ' . $site_name;

        $links_html = '';
        foreach ( $bookings as $b ) {
            $token  = get_post_meta( $b->ID, '_cm_booking_token', true );
            if ( ! $token ) continue;

            $url     = self::get_portal_url( $b->ID );
            $status  = get_post_meta( $b->ID, '_cm_booking_status', true ) ?: 'nieuw';
            $service = get_post_meta( $b->ID, '_cm_booking_service', true );
            $date    = get_the_date( 'd-m-Y', $b->ID );

            $links_html .= '
            <a href="' . esc_url( $url ) . '" style="display:block;padding:16px 20px;background:#f8f9fa;border-radius:10px;margin-bottom:10px;text-decoration:none;border:1px solid #e9ecef;">
                <span style="font-weight:600;color:#2d3436;font-size:15px;">' . esc_html( $service ) . '</span><br>
                <span style="color:#6c757d;font-size:13px;">' . esc_html( $date ) . ' &bull; Status: ' . esc_html( ucfirst( $status ) ) . '</span>
            </a>';
        }

        if ( empty( $links_html ) ) return;

        $html = '
<!DOCTYPE html><html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f0f2f5;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td style="padding:40px 20px;">
<table role="presentation" width="100%" style="max-width:540px;margin:0 auto;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
    <tr><td style="background:' . esc_attr( $primary ) . ';padding:28px 32px;">
        <h1 style="margin:0;color:#ffffff;font-size:20px;font-family:Arial,sans-serif;">Uw boekingen</h1>
    </td></tr>
    <tr><td style="padding:32px;">
        <p style="color:#2d3436;font-family:Arial,sans-serif;margin:0 0 20px;">Klik op een boeking hieronder om de details en status te bekijken:</p>
        ' . $links_html . '
        <p style="color:#999;font-size:12px;font-family:Arial,sans-serif;margin-top:24px;">Deze links zijn persoonlijk en beveiligd. Deel ze niet met anderen.</p>
    </td></tr>
    <tr><td style="padding:20px 32px;background:#f8f9fa;border-top:1px solid #e9ecef;">
        <p style="margin:0;color:#6c757d;font-size:12px;font-family:Arial,sans-serif;">' . esc_html( $site_name ) . '</p>
    </td></tr>
</table>
</td></tr></table>
</body></html>';

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $email, $subject, $html, $headers );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Genereer en sla een uniek boekingstoken op.
     */
    public static function generate_token( $booking_id ) {
        $token = bin2hex( random_bytes( 16 ) );
        update_post_meta( $booking_id, '_cm_booking_token', $token );
        return $token;
    }

    /**
     * Haal de volledige portaal-URL op voor een boeking.
     */
    public static function get_portal_url( $booking_id ) {
        $token = get_post_meta( $booking_id, '_cm_booking_token', true );
        if ( ! $token ) {
            $token = self::generate_token( $booking_id );
        }

        $portal_page_id = intval( get_option( 'cmcalc_portal_page_id' ) );
        $base_url = $portal_page_id ? get_permalink( $portal_page_id ) : home_url( '/' );

        return add_query_arg( 'booking_ref', rawurlencode( $token ), $base_url );
    }

    private static function error_box( $message, $radius ) {
        return '<div style="padding:24px 28px;background:#fff5f5;border:1px solid #feb2b2;border-radius:' . intval( $radius ) . 'px;color:#c53030;font-family:Arial,sans-serif;">'
            . '<strong>Fout:</strong> ' . esc_html( $message )
            . '</div>';
    }

    // ─── Stijlen ─────────────────────────────────────────────────────────────

    private static function render_portal_styles( $primary, $accent, $radius ) {
        ?>
        <style>
        .cmcalc-portal {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            color: #2d3436;
            max-width: 860px;
            margin: 0 auto;
        }
        .cmp-header {
            text-align: center;
            padding: 32px 0 24px;
        }
        .cmp-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .cmp-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--cm-primary);
            margin: 0 0 6px;
        }
        .cmp-subtitle {
            color: #6c757d;
            font-size: 15px;
            max-width: 440px;
            margin: 0 auto;
            line-height: 1.5;
        }
        .cmp-meta {
            color: #6c757d;
            font-size: 13px;
            margin: 0;
        }
        .cmp-meta code {
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }

        /* Tijdlijn */
        .cmp-timeline {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin: 24px 0;
            flex-wrap: wrap;
        }
        .cmp-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            position: relative;
        }
        .cmp-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 16px;
            left: calc(50% + 16px);
            width: calc(100% - 8px);
            height: 2px;
            background: #e9ecef;
            z-index: 0;
        }
        .cmp-step--done .cmp-step-dot,
        .cmp-step--active .cmp-step-dot {
            color: #fff;
            font-size: 11px;
        }
        .cmp-step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            position: relative;
            z-index: 1;
        }
        .cmp-step-label {
            font-size: 12px;
            color: #6c757d;
            white-space: nowrap;
            min-width: 80px;
            text-align: center;
        }
        .cmp-step--active .cmp-step-label {
            color: var(--cm-primary);
            font-weight: 600;
        }
        .cmp-step--done .cmp-step-label {
            color: #6c757d;
        }

        /* Grid */
        .cmp-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 640px) {
            .cmp-grid { grid-template-columns: 1fr; }
        }

        /* Card */
        .cmp-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: var(--cm-radius);
            padding: 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,.05);
        }
        .cmp-card-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--cm-primary);
            margin: 0 0 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f3f5;
        }

        /* Tabel */
        .cmp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .cmp-table th {
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 0 0 8px;
            border-bottom: 1px solid #f1f3f5;
        }
        .cmp-table td {
            padding: 10px 0;
            border-bottom: 1px solid #f9f9f9;
            vertical-align: top;
        }
        .cmp-row-extra td {
            padding: 6px 0;
            font-size: 13px;
        }
        .cmp-row-total td {
            padding-top: 12px;
            font-size: 16px;
            border-top: 2px solid #e9ecef;
        }
        .cmp-subopts {
            margin-top: 4px;
        }
        .cmp-subopts span {
            display: block;
            font-size: 12px;
            color: #6c757d;
        }

        /* DL */
        .cmp-dl {
            margin: 0;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 8px 16px;
            font-size: 14px;
        }
        .cmp-dl dt {
            font-weight: 600;
            color: #6c757d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            align-self: center;
        }
        .cmp-dl dd {
            margin: 0;
            color: #2d3436;
        }
        .cmp-message-block {
            margin-top: 16px;
            padding: 14px 16px;
            background: #fff8e1;
            border-radius: 8px;
            border-left: 3px solid #ffc107;
        }
        .cmp-message-label {
            margin: 0 0 6px;
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
        }
        .cmp-message-text {
            margin: 0;
            font-size: 14px;
            line-height: 1.5;
            color: #2d3436;
        }

        /* Lookup form */
        .cmp-lookup-wrap {
            text-align: center;
            padding: 48px 24px;
            max-width: 480px;
            margin: 0 auto;
        }
        .cmp-lookup-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .cmp-form {
            margin-top: 24px;
        }
        .cmp-input-row {
            display: flex;
            gap: 8px;
        }
        .cmp-input-row input {
            flex: 1;
            padding: 12px 16px;
            border: 1.5px solid #dee2e6;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            transition: border-color .2s;
        }
        .cmp-input-row input:focus {
            border-color: var(--cm-accent);
        }
        .cmp-input-row button {
            padding: 12px 20px;
            background: var(--cm-primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            transition: opacity .2s;
        }
        .cmp-input-row button:hover { opacity: .9; }
        .cmp-input-row button:disabled { opacity: .5; cursor: default; }
        .cmp-msg {
            margin-top: 14px;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
        }
        .cmp-msg--success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .cmp-msg--error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        /* Footer */
        .cmp-footer {
            text-align: center;
            color: #6c757d;
            font-size: 13px;
            padding: 16px 0 32px;
        }
        </style>
        <?php
    }
}
