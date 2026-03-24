<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * CMCalc_Boss_Portal
 * Boss-tier klantportaal met accounts, dashboard, boekingshistorie en berichten.
 * Shortcodes: [cmcalc_boss_portal]  [cmcalc_boss_login]
 */
class CMCalc_Boss_Portal {

    const TABLE_ACCOUNTS  = 'cmcalc_accounts';
    const TABLE_MESSAGES  = 'cmcalc_messages';
    const COOKIE_NAME     = 'cmcalc_boss_session';
    const COOKIE_LIFETIME = 30 * DAY_IN_SECONDS;

    // ─── Init ────────────────────────────────────────────────────────────────

    public static function register() {
        add_shortcode( 'cmcalc_boss_portal', array( __CLASS__, 'render_portal' ) );
        add_shortcode( 'cmcalc_boss_login',  array( __CLASS__, 'render_login' ) );

        add_action( 'wp_ajax_nopriv_cmcalc_boss_register',   array( __CLASS__, 'handle_register' ) );
        add_action( 'wp_ajax_nopriv_cmcalc_boss_login',      array( __CLASS__, 'handle_login' ) );
        add_action( 'wp_ajax_nopriv_cmcalc_boss_logout',     array( __CLASS__, 'handle_logout' ) );
        add_action( 'wp_ajax_cmcalc_boss_logout',            array( __CLASS__, 'handle_logout' ) );
        add_action( 'wp_ajax_nopriv_cmcalc_boss_send_msg',   array( __CLASS__, 'handle_send_message' ) );
        add_action( 'wp_ajax_cmcalc_boss_send_msg',          array( __CLASS__, 'handle_send_message' ) );
        add_action( 'wp_ajax_nopriv_cmcalc_boss_get_msgs',   array( __CLASS__, 'handle_get_messages' ) );
        add_action( 'wp_ajax_cmcalc_boss_get_msgs',          array( __CLASS__, 'handle_get_messages' ) );
        add_action( 'wp_ajax_nopriv_cmcalc_boss_update_profile', array( __CLASS__, 'handle_update_profile' ) );
        add_action( 'wp_ajax_cmcalc_boss_update_profile',    array( __CLASS__, 'handle_update_profile' ) );

        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );

        // Admin: berichten beheren
        add_action( 'wp_ajax_cmcalc_admin_reply_message',    array( __CLASS__, 'handle_admin_reply' ) );
        add_action( 'wp_ajax_cmcalc_admin_get_conversations',array( __CLASS__, 'handle_admin_conversations' ) );
    }

    public static function install_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql_accounts = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}" . self::TABLE_ACCOUNTS . " (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email         VARCHAR(200)    NOT NULL UNIQUE,
            password_hash VARCHAR(255)    NOT NULL,
            first_name    VARCHAR(100)    NOT NULL DEFAULT '',
            last_name     VARCHAR(100)    NOT NULL DEFAULT '',
            company_name  VARCHAR(200)    NOT NULL DEFAULT '',
            phone         VARCHAR(50)     NOT NULL DEFAULT '',
            session_token VARCHAR(64)     DEFAULT NULL,
            session_expires DATETIME      DEFAULT NULL,
            verified      TINYINT(1)      NOT NULL DEFAULT 0,
            verify_token  VARCHAR(64)     DEFAULT NULL,
            created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_token (session_token)
        ) $charset;";

        $sql_messages = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}" . self::TABLE_MESSAGES . " (
            id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            account_id   BIGINT UNSIGNED NOT NULL,
            direction    ENUM('in','out') NOT NULL DEFAULT 'in',
            subject      VARCHAR(300)    NOT NULL DEFAULT '',
            body         TEXT            NOT NULL,
            booking_id   BIGINT UNSIGNED DEFAULT NULL,
            is_read      TINYINT(1)      NOT NULL DEFAULT 0,
            created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY account_id (account_id),
            KEY is_read (is_read)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql_accounts );
        dbDelta( $sql_messages );
    }

    // ─── Session helpers ──────────────────────────────────────────────────────

    private static function get_current_account() {
        $token = $_COOKIE[ self::COOKIE_NAME ] ?? '';
        if ( ! $token ) return null;

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_ACCOUNTS;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE session_token = %s AND session_expires > NOW()",
            sanitize_text_field( $token )
        ) );
    }

    private static function set_session( $account_id ) {
        global $wpdb;
        $token   = bin2hex( random_bytes( 32 ) );
        $expires = date( 'Y-m-d H:i:s', time() + self::COOKIE_LIFETIME );
        $wpdb->update(
            $wpdb->prefix . self::TABLE_ACCOUNTS,
            array( 'session_token' => $token, 'session_expires' => $expires ),
            array( 'id' => $account_id )
        );
        setcookie( self::COOKIE_NAME, $token, time() + self::COOKIE_LIFETIME, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        return $token;
    }

    private static function clear_session( $account_id ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . self::TABLE_ACCOUNTS,
            array( 'session_token' => null, 'session_expires' => null ),
            array( 'id' => $account_id )
        );
        setcookie( self::COOKIE_NAME, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
    }

    // ─── AJAX: Register ───────────────────────────────────────────────────────

    public static function handle_register() {
        check_ajax_referer( 'cmcalc_boss_nonce', 'nonce' );

        $email      = sanitize_email( $_POST['email'] ?? '' );
        $password   = $_POST['password'] ?? '';
        $first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
        $last_name  = sanitize_text_field( $_POST['last_name'] ?? '' );
        $company    = sanitize_text_field( $_POST['company_name'] ?? '' );
        $phone      = sanitize_text_field( $_POST['phone'] ?? '' );

        if ( ! is_email( $email ) )       wp_send_json_error( 'Ongeldig e-mailadres.' );
        if ( strlen( $password ) < 8 )    wp_send_json_error( 'Wachtwoord minimaal 8 tekens.' );
        if ( ! $first_name || ! $last_name ) wp_send_json_error( 'Naam is verplicht.' );

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_ACCOUNTS;

        if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE email = %s", $email ) ) ) {
            wp_send_json_error( 'E-mailadres is al geregistreerd.' );
        }

        $verify_token = bin2hex( random_bytes( 16 ) );
        $inserted = $wpdb->insert( $table, array(
            'email'         => $email,
            'password_hash' => password_hash( $password, PASSWORD_DEFAULT ),
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'company_name'  => $company,
            'phone'         => $phone,
            'verified'      => 1, // Direct actief (geen e-mailverificatie voor nu)
            'verify_token'  => $verify_token,
        ) );

        if ( ! $inserted ) wp_send_json_error( 'Registratie mislukt. Probeer opnieuw.' );

        $account_id = $wpdb->insert_id;
        self::set_session( $account_id );

        wp_send_json_success( array( 'message' => 'Account aangemaakt! U bent nu ingelogd.' ) );
    }

    // ─── AJAX: Login ──────────────────────────────────────────────────────────

    public static function handle_login() {
        check_ajax_referer( 'cmcalc_boss_nonce', 'nonce' );

        $email    = sanitize_email( $_POST['email'] ?? '' );
        $password = $_POST['password'] ?? '';

        if ( ! $email || ! $password ) wp_send_json_error( 'Vul alle velden in.' );

        global $wpdb;
        $table   = $wpdb->prefix . self::TABLE_ACCOUNTS;
        $account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE email = %s", $email ) );

        if ( ! $account || ! password_verify( $password, $account->password_hash ) ) {
            wp_send_json_error( 'Onjuist e-mailadres of wachtwoord.' );
        }

        self::set_session( $account->id );
        wp_send_json_success( array( 'message' => 'Ingelogd!' ) );
    }

    // ─── AJAX: Logout ─────────────────────────────────────────────────────────

    public static function handle_logout() {
        $account = self::get_current_account();
        if ( $account ) self::clear_session( $account->id );
        wp_send_json_success();
    }

    // ─── AJAX: Send Message ───────────────────────────────────────────────────

    public static function handle_send_message() {
        check_ajax_referer( 'cmcalc_boss_nonce', 'nonce' );
        $account = self::get_current_account();
        if ( ! $account ) wp_send_json_error( 'Niet ingelogd.' );

        $subject    = sanitize_text_field( $_POST['subject'] ?? 'Bericht van klant' );
        $body       = sanitize_textarea_field( $_POST['body'] ?? '' );
        $booking_id = intval( $_POST['booking_id'] ?? 0 );

        if ( strlen( $body ) < 5 ) wp_send_json_error( 'Bericht is te kort.' );

        global $wpdb;
        $wpdb->insert( $wpdb->prefix . self::TABLE_MESSAGES, array(
            'account_id' => $account->id,
            'direction'  => 'in',
            'subject'    => $subject,
            'body'       => $body,
            'booking_id' => $booking_id ?: null,
        ) );

        // Notify admin
        $admin_email = get_option( 'admin_email' );
        wp_mail( $admin_email,
            '[CleanMasterzz] Nieuw bericht van ' . $account->first_name . ' ' . $account->last_name,
            "Klant: {$account->first_name} {$account->last_name} ({$account->email})\n\nOnderwerp: {$subject}\n\n{$body}\n\nBekijk in admin: " . admin_url( 'admin.php?page=cmcalc-berichten' )
        );

        wp_send_json_success( array( 'message' => 'Bericht verzonden!' ) );
    }

    // ─── AJAX: Get Messages ───────────────────────────────────────────────────

    public static function handle_get_messages() {
        check_ajax_referer( 'cmcalc_boss_nonce', 'nonce' );
        $account = self::get_current_account();
        if ( ! $account ) wp_send_json_error( 'Niet ingelogd.' );

        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_MESSAGES;
        $msgs  = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE account_id = %d ORDER BY created_at DESC LIMIT 50",
            $account->id
        ) );

        // Mark as read
        $wpdb->update( $table, array( 'is_read' => 1 ), array( 'account_id' => $account->id, 'is_read' => 0 ) );

        wp_send_json_success( $msgs );
    }

    // ─── AJAX: Update Profile ────────────────────────────────────────────────

    public static function handle_update_profile() {
        check_ajax_referer( 'cmcalc_boss_nonce', 'nonce' );
        $account = self::get_current_account();
        if ( ! $account ) wp_send_json_error( 'Niet ingelogd.' );

        global $wpdb;
        $data = array(
            'first_name'   => sanitize_text_field( $_POST['first_name'] ?? $account->first_name ),
            'last_name'    => sanitize_text_field( $_POST['last_name']  ?? $account->last_name ),
            'company_name' => sanitize_text_field( $_POST['company_name'] ?? $account->company_name ),
            'phone'        => sanitize_text_field( $_POST['phone'] ?? $account->phone ),
        );

        if ( ! empty( $_POST['new_password'] ) ) {
            if ( strlen( $_POST['new_password'] ) < 8 ) wp_send_json_error( 'Nieuw wachtwoord minimaal 8 tekens.' );
            if ( ! password_verify( $_POST['current_password'] ?? '', $account->password_hash ) ) {
                wp_send_json_error( 'Huidig wachtwoord klopt niet.' );
            }
            $data['password_hash'] = password_hash( $_POST['new_password'], PASSWORD_DEFAULT );
        }

        $wpdb->update( $wpdb->prefix . self::TABLE_ACCOUNTS, $data, array( 'id' => $account->id ) );
        wp_send_json_success( array( 'message' => 'Profiel bijgewerkt!' ) );
    }

    // ─── AJAX: Admin reply ────────────────────────────────────────────────────

    public static function handle_admin_reply() {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang.' );
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );

        $account_id = intval( $_POST['account_id'] ?? 0 );
        $body       = sanitize_textarea_field( $_POST['body'] ?? '' );
        $subject    = sanitize_text_field( $_POST['subject'] ?? 'Antwoord van CleanMasterzz' );

        if ( ! $account_id || ! $body ) wp_send_json_error( 'Ongeldige gegevens.' );

        global $wpdb;
        $account = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}" . self::TABLE_ACCOUNTS . " WHERE id = %d",
            $account_id
        ) );
        if ( ! $account ) wp_send_json_error( 'Account niet gevonden.' );

        $wpdb->insert( $wpdb->prefix . self::TABLE_MESSAGES, array(
            'account_id' => $account_id,
            'direction'  => 'out',
            'subject'    => $subject,
            'body'       => $body,
        ) );

        // Mail naar klant
        wp_mail( $account->email,
            '[CleanMasterzz] ' . $subject,
            "Beste {$account->first_name},\n\n{$body}\n\nMet vriendelijke groet,\nHet CleanMasterzz team\n\nLog in voor meer details: " . home_url( '/klantportaal/' )
        );

        wp_send_json_success( array( 'message' => 'Antwoord verzonden naar ' . $account->email ) );
    }

    public static function handle_admin_conversations() {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang.' );
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );

        global $wpdb;
        $accounts_table = $wpdb->prefix . self::TABLE_ACCOUNTS;
        $messages_table = $wpdb->prefix . self::TABLE_MESSAGES;

        $conversations = $wpdb->get_results(
            "SELECT a.id, a.first_name, a.last_name, a.email, a.company_name,
                    COUNT(m.id) as total_messages,
                    SUM(CASE WHEN m.is_read = 0 AND m.direction = 'in' THEN 1 ELSE 0 END) as unread,
                    MAX(m.created_at) as last_message
             FROM {$accounts_table} a
             LEFT JOIN {$messages_table} m ON m.account_id = a.id
             GROUP BY a.id
             ORDER BY last_message DESC"
        );

        wp_send_json_success( $conversations );
    }

    // ─── Assets ───────────────────────────────────────────────────────────────

    public static function enqueue_assets() {
        if ( ! has_shortcode( get_post()->post_content ?? '', 'cmcalc_boss_portal' ) &&
             ! has_shortcode( get_post()->post_content ?? '', 'cmcalc_boss_login' ) ) return;

        wp_enqueue_style( 'cmcalc-boss-portal',
            CMCALC_PLUGIN_URL . 'public/css/boss-portal.css',
            array(), CMCALC_VERSION
        );
        wp_enqueue_script( 'cmcalc-boss-portal',
            CMCALC_PLUGIN_URL . 'public/js/boss-portal.js',
            array( 'jquery' ), CMCALC_VERSION, true
        );
        wp_localize_script( 'cmcalc-boss-portal', 'cmcalcBoss', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'cmcalc_boss_nonce' ),
        ) );
    }

    // ─── Shortcode: Login ─────────────────────────────────────────────────────

    public static function render_login( $atts ) {
        $account = self::get_current_account();
        if ( $account ) {
            return '<p style="text-align:center;">U bent ingelogd als <strong>' . esc_html( $account->first_name ) . '</strong>. <a href="?tab=dashboard">Ga naar portaal</a></p>';
        }

        ob_start();
        $styles = CMCalc_Admin::get_styles();
        $primary = $styles['primary_color'] ?? '#1B2A4A';
        $accent  = $styles['accent_color']  ?? '#4DA8DA';
        ?>
        <div class="cm-boss-auth" style="--cm-primary:<?php echo esc_attr($primary); ?>;--cm-accent:<?php echo esc_attr($accent); ?>">
            <div class="cm-boss-tabs">
                <button class="cm-boss-tab active" data-tab="login">Inloggen</button>
                <button class="cm-boss-tab" data-tab="register">Account aanmaken</button>
            </div>

            <!-- Login form -->
            <div class="cm-boss-tab-content active" id="cm-tab-login">
                <form class="cm-boss-form" id="cmBossLoginForm">
                    <div class="cm-boss-field">
                        <label>E-mailadres</label>
                        <input type="email" name="email" required placeholder="uw@email.nl">
                    </div>
                    <div class="cm-boss-field">
                        <label>Wachtwoord</label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                    <div class="cm-boss-notice" id="cmBossLoginNotice"></div>
                    <button type="submit" class="cm-boss-btn">Inloggen</button>
                </form>
            </div>

            <!-- Register form -->
            <div class="cm-boss-tab-content" id="cm-tab-register">
                <form class="cm-boss-form" id="cmBossRegisterForm">
                    <div class="cm-boss-field-row">
                        <div class="cm-boss-field">
                            <label>Voornaam</label>
                            <input type="text" name="first_name" required placeholder="Jan">
                        </div>
                        <div class="cm-boss-field">
                            <label>Achternaam</label>
                            <input type="text" name="last_name" required placeholder="Jansen">
                        </div>
                    </div>
                    <div class="cm-boss-field">
                        <label>Bedrijfsnaam</label>
                        <input type="text" name="company_name" placeholder="Schoonmaakbedrijf BV">
                    </div>
                    <div class="cm-boss-field">
                        <label>E-mailadres</label>
                        <input type="email" name="email" required placeholder="uw@email.nl">
                    </div>
                    <div class="cm-boss-field">
                        <label>Telefoonnummer</label>
                        <input type="tel" name="phone" placeholder="06 12345678">
                    </div>
                    <div class="cm-boss-field">
                        <label>Wachtwoord <small>(min. 8 tekens)</small></label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                    <div class="cm-boss-notice" id="cmBossRegisterNotice"></div>
                    <button type="submit" class="cm-boss-btn">Account aanmaken</button>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // ─── Shortcode: Portal ────────────────────────────────────────────────────

    public static function render_portal( $atts ) {
        $account = self::get_current_account();

        if ( ! $account ) {
            return '<div class="cm-boss-portal-gate">'
                 . '<p>Log in om uw portaal te bekijken.</p>'
                 . do_shortcode( '[cmcalc_boss_login]' )
                 . '</div>';
        }

        $tab = sanitize_key( $_GET['tab'] ?? 'dashboard' );

        ob_start();
        $styles  = CMCalc_Admin::get_styles();
        $primary = $styles['primary_color'] ?? '#1B2A4A';
        $accent  = $styles['accent_color']  ?? '#4DA8DA';
        ?>
        <div class="cm-boss-portal" style="--cm-primary:<?php echo esc_attr($primary); ?>;--cm-accent:<?php echo esc_attr($accent); ?>">

            <!-- Sidebar nav -->
            <nav class="cm-boss-nav">
                <div class="cm-boss-nav-header">
                    <div class="cm-boss-avatar"><?php echo esc_html( strtoupper( substr( $account->first_name, 0, 1 ) . substr( $account->last_name, 0, 1 ) ) ); ?></div>
                    <div>
                        <div class="cm-boss-nav-name"><?php echo esc_html( $account->first_name . ' ' . $account->last_name ); ?></div>
                        <div class="cm-boss-nav-company"><?php echo esc_html( $account->company_name ?: $account->email ); ?></div>
                    </div>
                </div>
                <ul class="cm-boss-nav-links">
                    <li><a href="?tab=dashboard" class="<?php echo $tab === 'dashboard' ? 'active' : ''; ?>"><span class="dashicons dashicons-dashboard"></span> Dashboard</a></li>
                    <li><a href="?tab=boekingen" class="<?php echo $tab === 'boekingen' ? 'active' : ''; ?>"><span class="dashicons dashicons-calendar-alt"></span> Boekingen</a></li>
                    <li><a href="?tab=berichten" class="<?php echo $tab === 'berichten' ? 'active' : ''; ?>"><span class="dashicons dashicons-email"></span> Berichten</a></li>
                    <li><a href="?tab=profiel" class="<?php echo $tab === 'profiel' ? 'active' : ''; ?>"><span class="dashicons dashicons-admin-users"></span> Mijn profiel</a></li>
                </ul>
                <div class="cm-boss-nav-footer">
                    <button class="cm-boss-logout-btn" id="cmBossLogout"><span class="dashicons dashicons-exit"></span> Uitloggen</button>
                </div>
            </nav>

            <!-- Main content -->
            <main class="cm-boss-main">
                <?php
                switch ( $tab ) {
                    case 'boekingen': self::render_tab_boekingen( $account ); break;
                    case 'berichten': self::render_tab_berichten( $account ); break;
                    case 'profiel':   self::render_tab_profiel( $account );   break;
                    default:          self::render_tab_dashboard( $account );
                }
                ?>
            </main>
        </div>
        <?php
        return ob_get_clean();
    }

    // ─── Portal Tabs ─────────────────────────────────────────────────────────

    private static function render_tab_dashboard( $account ) {
        global $wpdb;

        // Boekingen ophalen voor dit account via email
        $boekingen = get_posts( array(
            'post_type'      => 'boeking',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array( array( 'key' => '_cm_email', 'value' => $account->email ) ),
        ) );

        $totaal    = count( $boekingen );
        $gepland   = 0; $afgerond  = 0; $totaal_waarde = 0;
        foreach ( $boekingen as $b ) {
            $status = get_post_meta( $b->ID, '_cm_status', true );
            if ( $status === 'gepland' || $status === 'bevestigd' ) $gepland++;
            if ( $status === 'afgerond' ) $afgerond++;
            $totaal_waarde += floatval( get_post_meta( $b->ID, '_cm_total', true ) );
        }

        $unread_msgs = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}" . self::TABLE_MESSAGES .
            " WHERE account_id = %d AND is_read = 0 AND direction = 'out'",
            $account->id
        ) );
        ?>
        <div class="cm-boss-tab-header">
            <h2>Goedemiddag, <?php echo esc_html( $account->first_name ); ?>! 👋</h2>
            <p>Overzicht van uw account bij CleanMasterzz.</p>
        </div>

        <div class="cm-boss-stats-grid">
            <div class="cm-boss-stat-card">
                <div class="cm-boss-stat-icon" style="background:linear-gradient(135deg,#3b82f6,#6366f1)">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="cm-boss-stat-value"><?php echo $totaal; ?></div>
                <div class="cm-boss-stat-label">Totaal boekingen</div>
            </div>
            <div class="cm-boss-stat-card">
                <div class="cm-boss-stat-icon" style="background:linear-gradient(135deg,#10b981,#06b6d4)">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="cm-boss-stat-value"><?php echo $gepland; ?></div>
                <div class="cm-boss-stat-label">Geplande schoonmaak</div>
            </div>
            <div class="cm-boss-stat-card">
                <div class="cm-boss-stat-icon" style="background:linear-gradient(135deg,#f59e0b,#ec4899)">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div class="cm-boss-stat-value">&euro;<?php echo number_format( $totaal_waarde, 2, ',', '.' ); ?></div>
                <div class="cm-boss-stat-label">Totale waarde</div>
            </div>
            <div class="cm-boss-stat-card <?php echo $unread_msgs ? 'has-badge' : ''; ?>">
                <div class="cm-boss-stat-icon" style="background:linear-gradient(135deg,#8b5cf6,#ec4899)">
                    <span class="dashicons dashicons-email-alt"></span>
                </div>
                <div class="cm-boss-stat-value"><?php echo $unread_msgs ?: 0; ?></div>
                <div class="cm-boss-stat-label">Ongelezen berichten</div>
            </div>
        </div>

        <!-- Recente boekingen -->
        <?php if ( $boekingen ) : ?>
        <div class="cm-boss-card" style="margin-top:24px;">
            <div class="cm-boss-card-header">
                <h3>Recente boekingen</h3>
                <a href="?tab=boekingen" class="cm-boss-link">Alles bekijken →</a>
            </div>
            <table class="cm-boss-table">
                <thead><tr><th>Datum</th><th>Dienst</th><th>Status</th><th>Bedrag</th></tr></thead>
                <tbody>
                <?php foreach ( array_slice( $boekingen, 0, 5 ) as $b ) :
                    $status = get_post_meta( $b->ID, '_cm_status', true ) ?: 'nieuw';
                    $dienst = get_post_meta( $b->ID, '_cm_service_name', true ) ?: get_the_title( $b );
                    $total  = floatval( get_post_meta( $b->ID, '_cm_total', true ) );
                    $date   = get_post_meta( $b->ID, '_cm_date', true ) ?: get_the_date( 'd-m-Y', $b );
                ?>
                <tr>
                    <td><?php echo esc_html( $date ); ?></td>
                    <td><?php echo esc_html( $dienst ); ?></td>
                    <td><span class="cm-boss-status cm-boss-status--<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
                    <td>&euro;<?php echo number_format( $total, 2, ',', '.' ); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php
    }

    private static function render_tab_boekingen( $account ) {
        $boekingen = get_posts( array(
            'post_type'      => 'boeking',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array( array( 'key' => '_cm_email', 'value' => $account->email ) ),
        ) );
        ?>
        <div class="cm-boss-tab-header">
            <h2>Mijn boekingen</h2>
            <p><?php echo count($boekingen); ?> boeking(en) gevonden.</p>
        </div>
        <?php if ( ! $boekingen ) : ?>
            <div class="cm-boss-empty"><span class="dashicons dashicons-calendar-alt"></span><p>Nog geen boekingen gevonden.</p></div>
        <?php else : ?>
        <div class="cm-boss-card">
            <table class="cm-boss-table cm-boss-table--full">
                <thead>
                    <tr><th>Ref.</th><th>Datum</th><th>Dienst</th><th>Adres</th><th>Status</th><th>Bedrag</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ( $boekingen as $b ) :
                    $status  = get_post_meta( $b->ID, '_cm_status', true ) ?: 'nieuw';
                    $dienst  = get_post_meta( $b->ID, '_cm_service_name', true ) ?: get_the_title( $b );
                    $adres   = trim( get_post_meta( $b->ID, '_cm_address', true ) . ' ' . get_post_meta( $b->ID, '_cm_city', true ) );
                    $total   = floatval( get_post_meta( $b->ID, '_cm_total', true ) );
                    $date    = get_post_meta( $b->ID, '_cm_date', true ) ?: get_the_date( 'd-m-Y', $b );
                    $token   = get_post_meta( $b->ID, '_cm_booking_token', true );
                ?>
                <tr>
                    <td><code>#<?php echo $b->ID; ?></code></td>
                    <td><?php echo esc_html( $date ); ?></td>
                    <td><?php echo esc_html( $dienst ); ?></td>
                    <td><?php echo esc_html( $adres ?: '—' ); ?></td>
                    <td><span class="cm-boss-status cm-boss-status--<?php echo esc_attr($status); ?>"><?php echo esc_html( ucfirst($status) ); ?></span></td>
                    <td>&euro;<?php echo number_format($total,2,',','.'); ?></td>
                    <td><?php if($token): ?><a href="<?php echo esc_url( add_query_arg('booking_ref',$token,home_url('/portaal/')) ); ?>" class="cm-boss-link" target="_blank">Bekijken →</a><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif;
    }

    private static function render_tab_berichten( $account ) {
        global $wpdb;
        $messages = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}" . self::TABLE_MESSAGES .
            " WHERE account_id = %d ORDER BY created_at DESC LIMIT 50",
            $account->id
        ) );
        // Mark read
        $wpdb->update( $wpdb->prefix . self::TABLE_MESSAGES, array('is_read'=>1), array('account_id'=>$account->id,'direction'=>'out','is_read'=>0) );
        ?>
        <div class="cm-boss-tab-header">
            <h2>Berichten</h2>
            <p>Communiceer direct met het CleanMasterzz team.</p>
        </div>

        <!-- Nieuw bericht -->
        <div class="cm-boss-card" style="margin-bottom:24px;">
            <div class="cm-boss-card-header"><h3>Nieuw bericht sturen</h3></div>
            <form id="cmBossMessageForm" class="cm-boss-form">
                <div class="cm-boss-field">
                    <label>Onderwerp</label>
                    <input type="text" name="subject" placeholder="Vraag over mijn boeking...">
                </div>
                <div class="cm-boss-field">
                    <label>Bericht</label>
                    <textarea name="body" rows="4" placeholder="Typ uw bericht hier..." required></textarea>
                </div>
                <div class="cm-boss-notice" id="cmBossMsgNotice"></div>
                <button type="submit" class="cm-boss-btn">Verstuur bericht</button>
            </form>
        </div>

        <!-- Berichtenlijst -->
        <div class="cm-boss-card">
            <div class="cm-boss-card-header"><h3>Berichtengeschiedenis</h3></div>
            <?php if ( ! $messages ) : ?>
                <div class="cm-boss-empty"><span class="dashicons dashicons-email"></span><p>Nog geen berichten.</p></div>
            <?php else : ?>
            <div class="cm-boss-messages">
                <?php foreach ( $messages as $msg ) : ?>
                <div class="cm-boss-message cm-boss-message--<?php echo esc_attr($msg->direction); ?>">
                    <div class="cm-boss-message-meta">
                        <span class="cm-boss-message-sender"><?php echo $msg->direction === 'in' ? 'U' : 'CleanMasterzz'; ?></span>
                        <span class="cm-boss-message-time"><?php echo date_i18n('d M Y H:i', strtotime($msg->created_at)); ?></span>
                    </div>
                    <div class="cm-boss-message-subject"><?php echo esc_html($msg->subject); ?></div>
                    <div class="cm-boss-message-body"><?php echo nl2br(esc_html($msg->body)); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_tab_profiel( $account ) {
        ?>
        <div class="cm-boss-tab-header">
            <h2>Mijn profiel</h2>
            <p>Beheer uw accountgegevens en wachtwoord.</p>
        </div>

        <div class="cm-boss-card">
            <div class="cm-boss-card-header"><h3>Persoonlijke gegevens</h3></div>
            <form id="cmBossProfileForm" class="cm-boss-form">
                <div class="cm-boss-field-row">
                    <div class="cm-boss-field">
                        <label>Voornaam</label>
                        <input type="text" name="first_name" value="<?php echo esc_attr($account->first_name); ?>" required>
                    </div>
                    <div class="cm-boss-field">
                        <label>Achternaam</label>
                        <input type="text" name="last_name" value="<?php echo esc_attr($account->last_name); ?>" required>
                    </div>
                </div>
                <div class="cm-boss-field">
                    <label>Bedrijfsnaam</label>
                    <input type="text" name="company_name" value="<?php echo esc_attr($account->company_name); ?>">
                </div>
                <div class="cm-boss-field">
                    <label>Telefoon</label>
                    <input type="tel" name="phone" value="<?php echo esc_attr($account->phone); ?>">
                </div>
                <div class="cm-boss-field">
                    <label>E-mailadres <small>(niet wijzigbaar)</small></label>
                    <input type="email" value="<?php echo esc_attr($account->email); ?>" disabled>
                </div>
                <hr style="margin:20px 0;border-color:var(--cmcalc-border);">
                <h4 style="margin:0 0 16px;">Wachtwoord wijzigen</h4>
                <div class="cm-boss-field">
                    <label>Huidig wachtwoord</label>
                    <input type="password" name="current_password" placeholder="••••••••">
                </div>
                <div class="cm-boss-field">
                    <label>Nieuw wachtwoord</label>
                    <input type="password" name="new_password" placeholder="••••••••">
                </div>
                <div class="cm-boss-notice" id="cmBossProfileNotice"></div>
                <button type="submit" class="cm-boss-btn">Opslaan</button>
            </form>
        </div>

        <div class="cm-boss-card" style="margin-top:20px;">
            <div class="cm-boss-card-header"><h3>Account info</h3></div>
            <div class="cm-boss-info-grid">
                <div><strong>Lid since</strong> <?php echo date_i18n('d F Y', strtotime($account->created_at)); ?></div>
                <div><strong>Account ID</strong> #<?php echo $account->id; ?></div>
                <div><strong>Status</strong> <span class="cm-boss-status cm-boss-status--actief">Actief</span></div>
            </div>
        </div>
        <?php
    }
}
