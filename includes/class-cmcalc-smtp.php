<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * CMCalc_SMTP
 * Volledig configureerbare SMTP-integratie via phpmailer_init.
 * Wachtwoord wordt AES-256-CBC versleuteld opgeslagen.
 */
class CMCalc_SMTP {

    public static function init() {
        add_action( 'phpmailer_init', array( __CLASS__, 'configure_phpmailer' ) );
    }

    /**
     * Hook into WordPress PHPMailer en configureer SMTP-instellingen.
     */
    public static function configure_phpmailer( $phpmailer ) {
        $s = self::get_settings();

        if ( empty( $s['enabled'] ) ) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host        = $s['host'];
        $phpmailer->Port        = intval( $s['port'] );
        $phpmailer->SMTPAuth    = ! empty( $s['username'] ) && ! empty( $s['password'] );
        $phpmailer->SMTPSecure  = $s['encryption']; // 'ssl', 'tls' of ''
        $phpmailer->SMTPTimeout = 15; // max 15 seconden wachten

        if ( $phpmailer->SMTPAuth ) {
            $phpmailer->Username = $s['username'];
            $phpmailer->Password = self::decrypt_password( $s['password'] );
        }

        if ( $s['encryption'] === '' ) {
            $phpmailer->SMTPAutoTLS = false;
        }

        if ( ! empty( $s['from_email'] ) ) {
            $phpmailer->From = $s['from_email'];
        }

        if ( ! empty( $s['from_name'] ) ) {
            $phpmailer->FromName = $s['from_name'];
        }
    }

    /**
     * Haal SMTP-instellingen op (met defaults).
     */
    public static function get_settings() {
        return wp_parse_args( get_option( 'cmcalc_smtp', array() ), self::get_defaults() );
    }

    /**
     * Standaard SMTP-instellingen.
     */
    public static function get_defaults() {
        return array(
            'enabled'    => false,
            'host'       => 'mailout.hostnet.nl',
            'port'       => 587,
            'encryption' => 'tls',
            'username'   => '',
            'password'   => '',
            'from_name'  => '',
            'from_email' => '',
        );
    }

    /**
     * Sla SMTP-instellingen op. Wachtwoord wordt versleuteld bewaard.
     */
    public static function save_settings( $data ) {
        $current = self::get_settings();

        $clean = array(
            'enabled'    => ! empty( $data['enabled'] ),
            'host'       => sanitize_text_field( $data['host'] ?? 'mailout.hostnet.nl' ),
            'port'       => max( 1, min( 65535, intval( $data['port'] ?? 587 ) ) ),
            'encryption' => in_array( $data['encryption'] ?? '', array( 'ssl', 'tls', '' ), true ) ? $data['encryption'] : 'tls',
            'username'   => sanitize_text_field( $data['username'] ?? '' ),
            'from_name'  => sanitize_text_field( $data['from_name'] ?? '' ),
            'from_email' => sanitize_email( $data['from_email'] ?? '' ),
        );

        // Wachtwoord alleen updaten als een nieuw wachtwoord is opgegeven.
        if ( ! empty( $data['password'] ) && $data['password'] !== '••••••••' ) {
            $clean['password'] = self::encrypt_password( sanitize_text_field( $data['password'] ) );
        } else {
            $clean['password'] = $current['password'];
        }

        update_option( 'cmcalc_smtp', $clean );
        return $clean;
    }

    /**
     * Versleutel wachtwoord met AES-256-CBC via WordPress-salt.
     */
    private static function encrypt_password( $password ) {
        if ( ! function_exists( 'openssl_encrypt' ) || empty( $password ) ) {
            return base64_encode( $password );
        }

        $key       = substr( hash( 'sha256', wp_salt( 'auth' ) ), 0, 32 );
        $iv_length = openssl_cipher_iv_length( 'AES-256-CBC' );
        $iv        = openssl_random_pseudo_bytes( $iv_length );
        $encrypted = openssl_encrypt( $password, 'AES-256-CBC', $key, 0, $iv );

        return 'v1:' . base64_encode( $iv . '::' . $encrypted );
    }

    /**
     * Ontsleutel het opgeslagen wachtwoord.
     */
    private static function decrypt_password( $stored ) {
        if ( empty( $stored ) ) {
            return '';
        }

        // Versioned format: 'v1:base64(...)'
        if ( strpos( $stored, 'v1:' ) === 0 ) {
            if ( ! function_exists( 'openssl_decrypt' ) ) {
                return '';
            }
            $raw   = base64_decode( substr( $stored, 3 ) );
            $parts = explode( '::', $raw, 2 );
            if ( count( $parts ) !== 2 ) {
                return '';
            }
            $key = substr( hash( 'sha256', wp_salt( 'auth' ) ), 0, 32 );
            return openssl_decrypt( $parts[1], 'AES-256-CBC', $key, 0, $parts[0] ) ?: '';
        }

        // Fallback: base64-only opslag (geen OpenSSL)
        return base64_decode( $stored ) ?: '';
    }

    /**
     * Stuur een testmail via de geconfigureerde SMTP.
     * Geeft bij falen een array terug met 'error' sleutel en diagnosehints.
     */
    public static function send_test_email( $to ) {
        $settings  = self::get_settings();
        $site      = get_bloginfo( 'name' );
        $mail_error = null;

        // Vang PHPMailer-fouten op via wp_mail_failed.
        $listener = function( $wp_error ) use ( &$mail_error ) {
            $mail_error = $wp_error->get_error_message();
        };
        add_action( 'wp_mail_failed', $listener );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        $subject = '[SMTP Test] ' . $site;
        $body    = '
<div style="font-family:Arial,sans-serif;max-width:480px;margin:0 auto;padding:32px;background:#f9f9f9;border-radius:12px;">
    <h2 style="color:#1B2A4A;margin-bottom:12px;">&#x2713; SMTP werkt correct</h2>
    <p style="color:#555;">Deze testmail is verzonden via <strong>' . esc_html( $settings['host'] ) . ':' . intval( $settings['port'] ) . '</strong>.</p>
    <p style="color:#555;">Van: ' . esc_html( $settings['from_name'] ?: $site ) . ' &lt;' . esc_html( $settings['from_email'] ?: get_option( 'admin_email' ) ) . '&gt;</p>
    <hr style="border:none;border-top:1px solid #e0e0e0;margin:20px 0;">
    <p style="color:#999;font-size:12px;">Verzonden door de Cleanmasterzz Calculator plugin.</p>
</div>';

        $result = wp_mail( $to, $subject, $body, $headers );

        remove_action( 'wp_mail_failed', $listener );

        if ( $result ) {
            return true;
        }

        // Bouw diagnose-hint op bij verbindingsfout.
        $hint = '';
        if ( $mail_error && strpos( $mail_error, '10060' ) !== false ) {
            $hint = 'Foutcode 10060 betekent dat de server geen verbinding kon maken met ' . esc_html( $settings['host'] ) . ' op poort ' . intval( $settings['port'] ) . '. '
                  . 'Mogelijke oorzaken: (1) uw hostingprovider blokkeert uitgaande SMTP-verbindingen, '
                  . '(2) verkeerd host of poortnummer, (3) firewall. '
                  . 'Controleer bij uw host of poort ' . intval( $settings['port'] ) . ' open staat voor uitgaande verbindingen.';
        }

        return array(
            'error' => $mail_error ?: 'Onbekende fout',
            'hint'  => $hint,
        );
    }

    /**
     * Geeft een veilig weergaveobject terug (zonder wachtwoord).
     */
    public static function get_safe_settings() {
        $s = self::get_settings();
        $s['password'] = empty( $s['password'] ) ? '' : '••••••••';
        return $s;
    }
}
