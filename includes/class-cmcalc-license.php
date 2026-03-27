<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * CMCalc_License
 *
 * Beheert licentie validatie, activatie en feature gating.
 * Communiceert met de externe License Server (op de VPS).
 *
 * Tiers:  free | pro | boss | agency
 *
 * Features per tier:
 *   FREE:   basis calculator, 1 bedrijf, email notificaties
 *   PRO:    + bedrijf wizard, analytics, PDF facturen, geavanceerde kortingen, kalender
 *   BOSS:   alles van PRO + boss portaal, multi-bedrijf geavanceerd, SMS, berichten
 *   AGENCY: alles + white-label, reseller dashboard, onbeperkt bedrijven
 */
class CMCalc_License {

    /** WordPress option keys */
    const OPT_KEY        = 'cmcalc_license_key';
    const OPT_DATA       = 'cmcalc_license_data';
    const OPT_CHECKED    = 'cmcalc_license_last_check';

    /** Cache duur: 24 uur. Na 7 dagen zonder hervalidatie: grace period */
    const CACHE_HOURS    = 24;
    const GRACE_DAYS     = 7;

    /** License server URL */
    const SERVER_URL     = 'http://185.228.82.252/licenses/api';

    /** Feature matrix per tier */
    private static $feature_matrix = array(
        // ── FREE ──────────────────────────────────────────────────────
        'free' => array(
            'calculator'             => true,
            'bookings'               => true,
            'basic_email'            => true,
            'basic_portal'           => true,
            'discount_codes'         => true,
            'single_company'         => true,
        ),
        // ── PRO ───────────────────────────────────────────────────────
        'pro' => array(
            'calculator'             => true,
            'bookings'               => true,
            'basic_email'            => true,
            'basic_portal'           => true,
            'discount_codes'         => true,
            'single_company'         => true,
            // Pro exclusief
            'company_wizard'         => true,
            'analytics'              => true,
            'pdf_invoices'           => true,
            'advanced_discounts'     => true,
            'calendar'               => true,
            'multi_company'          => true,
        ),
        // ── BOSS ──────────────────────────────────────────────────────
        'boss' => array(
            'calculator'             => true,
            'bookings'               => true,
            'basic_email'            => true,
            'basic_portal'           => true,
            'discount_codes'         => true,
            'single_company'         => true,
            'company_wizard'         => true,
            'analytics'              => true,
            'pdf_invoices'           => true,
            'advanced_discounts'     => true,
            'calendar'               => true,
            'multi_company'          => true,
            // Boss exclusief
            'boss_portal'            => true,
            'customer_accounts'      => true,
            'customer_messages'      => true,
            'sms_notifications'      => true,
            'company_employee_login' => true,
        ),
        // ── AGENCY ────────────────────────────────────────────────────
        'agency' => array(
            'calculator'             => true,
            'bookings'               => true,
            'basic_email'            => true,
            'basic_portal'           => true,
            'discount_codes'         => true,
            'single_company'         => true,
            'company_wizard'         => true,
            'analytics'              => true,
            'pdf_invoices'           => true,
            'advanced_discounts'     => true,
            'calendar'               => true,
            'multi_company'          => true,
            'boss_portal'            => true,
            'customer_accounts'      => true,
            'customer_messages'      => true,
            'sms_notifications'      => true,
            'company_employee_login' => true,
            // Agency exclusief
            'white_label'            => true,
            'reseller_dashboard'     => true,
            'unlimited_companies'    => true,
        ),
    );

    /**
     * Initialiseer: registreer cron voor dagelijkse validatie.
     */
    public static function init() {
        add_action( 'cmcalc_daily_license_check', array( __CLASS__, 'validate_remote' ) );
        if ( ! wp_next_scheduled( 'cmcalc_daily_license_check' ) ) {
            wp_schedule_event( time(), 'daily', 'cmcalc_daily_license_check' );
        }

        // AJAX handlers
        add_action( 'wp_ajax_cmcalc_activate_license',   array( __CLASS__, 'ajax_activate' ) );
        add_action( 'wp_ajax_cmcalc_deactivate_license', array( __CLASS__, 'ajax_deactivate' ) );
        add_action( 'wp_ajax_cmcalc_refresh_license',    array( __CLASS__, 'ajax_refresh' ) );
    }

    // ─────────────────────────────────────────────────────────────────
    // FEATURE GATING
    // ─────────────────────────────────────────────────────────────────

    /**
     * Controleer of een feature beschikbaar is op de huidige licentie.
     *
     * @param  string $feature  Feature ID (zie $feature_matrix)
     * @return bool
     */
    public static function has_feature( $feature ) {
        $tier   = self::get_tier();
        $matrix = self::$feature_matrix[ $tier ] ?? self::$feature_matrix['free'];
        return ! empty( $matrix[ $feature ] );
    }

    /**
     * Geef het huidige tier terug.
     *
     * @return string  'free' | 'pro' | 'boss' | 'agency'
     */
    public static function get_tier() {
        $data = self::get_cached_data();
        if ( ! $data || ! isset( $data['tier'] ) ) {
            return 'free';
        }
        // Grace period: als laatste check > 7 dagen geleden maar data bestaat, vertrouw nog op cache
        if ( ! $data['valid'] ) {
            $last_check = intval( get_option( self::OPT_CHECKED, 0 ) );
            $grace_until = $last_check + ( self::GRACE_DAYS * DAY_IN_SECONDS );
            if ( time() < $grace_until ) {
                return $data['tier']; // Gebruik gecachte tier binnen grace period
            }
            return 'free';
        }
        return $data['tier'];
    }

    /**
     * Geef leesbare tier naam.
     */
    public static function get_tier_label() {
        $labels = array(
            'free'   => 'Gratis',
            'pro'    => 'Professional',
            'boss'   => 'Boss',
            'agency' => 'Agency',
        );
        return $labels[ self::get_tier() ] ?? 'Gratis';
    }

    /**
     * Is de licentie momenteel actief en geldig?
     */
    public static function is_active() {
        return self::get_tier() !== 'free' || (bool) get_option( self::OPT_KEY );
    }

    /**
     * Is licentie in grace period (server niet bereikbaar maar wel ooit geldig)?
     */
    public static function in_grace_period() {
        $data = self::get_cached_data();
        if ( ! $data || $data['valid'] ) return false;
        $last_check  = intval( get_option( self::OPT_CHECKED, 0 ) );
        $grace_until = $last_check + ( self::GRACE_DAYS * DAY_IN_SECONDS );
        return time() < $grace_until;
    }

    // ─────────────────────────────────────────────────────────────────
    // ACTIVATIE / DEACTIVATIE
    // ─────────────────────────────────────────────────────────────────

    /**
     * Activeer een licentiesleutel op de huidige site.
     *
     * @param  string $key  Licentiesleutel
     * @return array        {success, message, data}
     */
    public static function activate( $key ) {
        $key    = strtoupper( sanitize_text_field( $key ) );
        $domain = self::get_domain();

        $response = self::api_request( 'activate', array(
            'license_key' => $key,
            'domain'      => $domain,
            'plugin_ver'  => CMCALC_VERSION,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'message' => 'Server niet bereikbaar: ' . $response->get_error_message() );
        }

        if ( $response['valid'] ?? false ) {
            update_option( self::OPT_KEY, $key );
            update_option( self::OPT_DATA, $response );
            update_option( self::OPT_CHECKED, time() );
            return array( 'success' => true, 'message' => 'Licentie geactiveerd!', 'data' => $response );
        }

        return array( 'success' => false, 'message' => $response['message'] ?? 'Activatie mislukt.' );
    }

    /**
     * Deactiveer de huidige licentie.
     */
    public static function deactivate() {
        $key    = get_option( self::OPT_KEY );
        $domain = self::get_domain();

        if ( $key ) {
            self::api_request( 'deactivate', array(
                'license_key' => $key,
                'domain'      => $domain,
            ) );
        }

        delete_option( self::OPT_KEY );
        delete_option( self::OPT_DATA );
        delete_option( self::OPT_CHECKED );

        return array( 'success' => true, 'message' => 'Licentie gedeactiveerd.' );
    }

    // ─────────────────────────────────────────────────────────────────
    // VALIDATIE
    // ─────────────────────────────────────────────────────────────────

    /**
     * Valideer licentie tegen de server (achtergrond, wordt gecached).
     */
    public static function validate_remote() {
        $key = get_option( self::OPT_KEY );
        if ( ! $key ) {
            update_option( self::OPT_DATA, array( 'valid' => false, 'tier' => 'free' ) );
            update_option( self::OPT_CHECKED, time() );
            return false;
        }

        $response = self::api_request( 'validate', array(
            'license_key' => $key,
            'domain'      => self::get_domain(),
        ) );

        if ( is_wp_error( $response ) ) {
            // Behoud oude data, reset niet — grace period blijft actief
            return false;
        }

        update_option( self::OPT_DATA, $response );
        update_option( self::OPT_CHECKED, time() );
        return $response['valid'] ?? false;
    }

    /**
     * Geef gecachte licentiedata.
     */
    public static function get_cached_data() {
        $data = get_option( self::OPT_DATA, null );
        if ( ! $data ) return null;
        return $data;
    }

    /**
     * Geef huidige licentiesleutel (gedeeltelijk verborgen voor weergave).
     */
    public static function get_masked_key() {
        $key = get_option( self::OPT_KEY, '' );
        if ( ! $key ) return '';
        // Toon CMCALC-XXXX-****-****-XXXX
        $parts = explode( '-', $key );
        if ( count( $parts ) === 5 ) {
            $parts[2] = '****';
            $parts[3] = '****';
            return implode( '-', $parts );
        }
        return substr( $key, 0, 10 ) . '...';
    }

    /**
     * Geef alle licentiedata voor de admin UI.
     */
    public static function get_status_info() {
        $data     = self::get_cached_data();
        $key      = get_option( self::OPT_KEY, '' );
        $last     = intval( get_option( self::OPT_CHECKED, 0 ) );
        $tier     = self::get_tier();
        $in_grace = self::in_grace_period();

        return array(
            'has_key'      => ! empty( $key ),
            'masked_key'   => self::get_masked_key(),
            'tier'         => $tier,
            'tier_label'   => self::get_tier_label(),
            'valid'        => $data['valid'] ?? false,
            'in_grace'     => $in_grace,
            'expires_at'   => $data['expires_at'] ?? null,
            'domain'       => $data['domain'] ?? self::get_domain(),
            'last_checked' => $last ? date( 'd-m-Y H:i', $last ) : 'Nooit',
            'features'     => self::$feature_matrix[ $tier ] ?? self::$feature_matrix['free'],
            'message'      => $data['message'] ?? '',
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // API COMMUNICATIE
    // ─────────────────────────────────────────────────────────────────

    /**
     * Stuur een request naar de License Server.
     *
     * @param  string $endpoint  'validate' | 'activate' | 'deactivate'
     * @param  array  $params
     * @return array|WP_Error
     */
    private static function api_request( $endpoint, $params ) {
        $url = rtrim( self::SERVER_URL, '/' ) . '/' . $endpoint;

        $response = wp_remote_post( $url, array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Plugin-Ver' => CMCALC_VERSION,
            ),
            'body'    => wp_json_encode( $params ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! is_array( $data ) ) {
            return new WP_Error( 'invalid_response', 'Ongeldige server response (HTTP ' . $code . ')' );
        }

        return $data;
    }

    /**
     * Haal het primaire domein op van de WordPress installatie.
     */
    private static function get_domain() {
        return wp_parse_url( get_site_url(), PHP_URL_HOST );
    }

    // ─────────────────────────────────────────────────────────────────
    // AJAX HANDLERS
    // ─────────────────────────────────────────────────────────────────

    public static function ajax_activate() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die();

        $key = sanitize_text_field( $_POST['license_key'] ?? '' );
        if ( ! $key ) {
            wp_send_json_error( 'Geen licentiesleutel opgegeven.' );
        }

        $result = self::activate( $key );
        if ( $result['success'] ) {
            wp_send_json_success( array(
                'message' => $result['message'],
                'status'  => self::get_status_info(),
            ) );
        } else {
            wp_send_json_error( $result['message'] );
        }
    }

    public static function ajax_deactivate() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die();

        $result = self::deactivate();
        wp_send_json_success( array(
            'message' => $result['message'],
            'status'  => self::get_status_info(),
        ) );
    }

    public static function ajax_refresh() {
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die();

        $valid = self::validate_remote();
        wp_send_json_success( array(
            'valid'  => $valid,
            'status' => self::get_status_info(),
        ) );
    }

    // ─────────────────────────────────────────────────────────────────
    // HELPER: Premium Blokkade HTML
    // ─────────────────────────────────────────────────────────────────

    /**
     * Geef een "upgrade nodig" overlay terug voor premium functies.
     *
     * @param  string $feature_label  Naam van de feature (voor de gebruiker)
     * @param  string $required_tier  'pro' | 'boss' | 'agency'
     * @return string  HTML
     */
    public static function gate_html( $feature_label, $required_tier = 'pro' ) {
        $tier_labels = array(
            'pro'    => 'Professional',
            'boss'   => 'Boss',
            'agency' => 'Agency',
        );
        $tier_label = $tier_labels[ $required_tier ] ?? 'Pro';
        $upgrade_url = admin_url( 'admin.php?page=cmcalc-dashboard&tab=licentie' );

        ob_start();
        ?>
        <div class="cmcalc-premium-gate">
            <div class="cmcalc-premium-gate-inner">
                <div class="cmcalc-premium-gate-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                </div>
                <h3><?php echo esc_html( $feature_label ); ?></h3>
                <p>Deze functie vereist een <strong><?php echo esc_html( $tier_label ); ?></strong> licentie of hoger.</p>
                <a href="<?php echo esc_url( $upgrade_url ); ?>" class="cmcalc-btn-primary" style="display:inline-block;margin-top:12px;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600;">
                    Upgrade nu
                </a>
            </div>
        </div>
        <style>
        .cmcalc-premium-gate {
            background: linear-gradient(135deg, #f8f9ff, #fff0f6);
            border: 2px dashed #c084fc;
            border-radius: 16px;
            padding: 48px 24px;
            text-align: center;
            margin: 20px 0;
        }
        .cmcalc-premium-gate-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #a855f7, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: #fff;
        }
        .cmcalc-premium-gate h3 {
            font-size: 18px;
            color: #1B2A4A;
            margin: 0 0 8px;
        }
        .cmcalc-premium-gate p {
            color: #64748b;
            margin: 0;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Database tabellen aanmaken voor premium features.
     * Wordt aangeroepen bij activatie als licentie Pro+ is.
     */
    public static function maybe_create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        // Klantaccounts (Boss+)
        $sql_customers = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cmcalc_customers (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            email           VARCHAR(255) NOT NULL UNIQUE,
            name            VARCHAR(255),
            phone           VARCHAR(30),
            password_hash   VARCHAR(255),
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login      DATETIME,
            verified        TINYINT(1) DEFAULT 0
        ) $charset;";

        // Klantsessies (Boss+)
        $sql_sessions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cmcalc_customer_sessions (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            token       VARCHAR(64) NOT NULL UNIQUE,
            expires_at  DATETIME NOT NULL,
            ip          VARCHAR(45),
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset;";

        // Berichten (Boss+)
        $sql_messages = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cmcalc_messages (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            booking_id  INT NOT NULL,
            customer_id INT,
            from_type   ENUM('customer','admin') NOT NULL,
            message     TEXT NOT NULL,
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            read_at     DATETIME
        ) $charset;";

        // Facturen (Pro+)
        $sql_invoices = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cmcalc_invoices (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number  VARCHAR(30) NOT NULL UNIQUE,
            booking_id      INT NOT NULL,
            bedrijf_id      INT,
            status          ENUM('concept','verzonden','betaald') DEFAULT 'concept',
            total_excl      DECIMAL(10,2),
            btw_amount      DECIMAL(10,2),
            total_incl      DECIMAL(10,2),
            pdf_path        VARCHAR(500),
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_customers );
        dbDelta( $sql_sessions );
        dbDelta( $sql_messages );
        dbDelta( $sql_invoices );
    }
}
