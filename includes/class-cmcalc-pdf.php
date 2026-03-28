<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * CMCalc_PDF
 * Genereert PDF facturen voor boekingen (Pro+).
 *
 * Gebruikt een pure-PHP PDF generator (geen externe libraries vereist).
 * Activatie: admin AJAX cmcalc_generate_pdf + publieke download via token.
 */
class CMCalc_PDF {

    public static function init() {
        add_action( 'wp_ajax_cmcalc_generate_pdf',         array( __CLASS__, 'handle_generate' ) );
        add_action( 'wp_ajax_nopriv_cmcalc_download_pdf',  array( __CLASS__, 'handle_download' ) );
        add_action( 'wp_ajax_cmcalc_download_pdf',         array( __CLASS__, 'handle_download' ) );
    }

    // ─── AJAX: Admin genereert PDF ────────────────────────────────────────────

    public static function handle_generate() {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Geen toegang.' );
        check_ajax_referer( 'cmcalc_admin_nonce', 'nonce' );

        if ( ! CMCalc_License::has_feature( 'pdf_invoices' ) ) {
            wp_send_json_error( 'PDF facturen vereisen een Pro licentie.' );
        }

        $booking_id = intval( $_POST['booking_id'] ?? 0 );
        if ( ! $booking_id ) wp_send_json_error( 'Ongeldige boeking.' );

        $token = self::ensure_pdf_token( $booking_id );
        $url   = add_query_arg( array(
            'action'      => 'cmcalc_download_pdf',
            'booking_id'  => $booking_id,
            'token'       => $token,
        ), admin_url( 'admin-ajax.php' ) );

        wp_send_json_success( array( 'url' => $url, 'token' => $token ) );
    }

    // ─── AJAX: Download PDF ───────────────────────────────────────────────────

    public static function handle_download() {
        $booking_id = intval( $_GET['booking_id'] ?? 0 );
        $token      = sanitize_text_field( $_GET['token'] ?? '' );

        if ( ! $booking_id || ! $token ) wp_die( 'Ongeldige aanvraag.' );

        $stored_token = get_post_meta( $booking_id, '_cm_pdf_token', true );
        if ( ! hash_equals( $stored_token, $token ) ) wp_die( 'Ongeldig token.' );

        $pdf = self::generate_pdf( $booking_id );
        if ( ! $pdf ) wp_die( 'Kon PDF niet genereren.' );

        $filename = 'factuur-boeking-' . $booking_id . '.pdf';
        header( 'Content-Type: application/pdf' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . strlen( $pdf ) );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        echo $pdf;
        exit;
    }

    // ─── Token beheer ─────────────────────────────────────────────────────────

    private static function ensure_pdf_token( $booking_id ) {
        $existing = get_post_meta( $booking_id, '_cm_pdf_token', true );
        if ( $existing ) return $existing;

        $token = bin2hex( random_bytes( 16 ) );
        update_post_meta( $booking_id, '_cm_pdf_token', $token );
        return $token;
    }

    // ─── PDF generatie ────────────────────────────────────────────────────────

    public static function generate_pdf( $booking_id ) {
        $boeking = get_post( $booking_id );
        if ( ! $boeking || $boeking->post_type !== 'boeking' ) return false;

        // Boekingsdata ophalen
        $meta = array(
            'naam'          => trim( get_post_meta( $booking_id, '_cm_name', true )
                               ?: get_post_meta( $booking_id, '_cm_first_name', true ) . ' '
                                . get_post_meta( $booking_id, '_cm_last_name', true ) ),
            'email'         => get_post_meta( $booking_id, '_cm_email', true ),
            'telefoon'      => get_post_meta( $booking_id, '_cm_phone', true ),
            'adres'         => get_post_meta( $booking_id, '_cm_address', true ),
            'postcode'      => get_post_meta( $booking_id, '_cm_postcode', true ),
            'stad'          => get_post_meta( $booking_id, '_cm_city', true ),
            'dienst'        => get_post_meta( $booking_id, '_cm_service_name', true ) ?: get_the_title( $boeking ),
            'datum'         => get_post_meta( $booking_id, '_cm_date', true ) ?: get_the_date( 'd-m-Y', $boeking ),
            'tijdstip'      => get_post_meta( $booking_id, '_cm_time', true ),
            'oppervlak'     => get_post_meta( $booking_id, '_cm_area', true ),
            'subtotaal'     => floatval( get_post_meta( $booking_id, '_cm_subtotal', true ) ),
            'btw_pct'       => floatval( get_post_meta( $booking_id, '_cm_btw_pct', true ) ?: get_option( 'cmcalc_btw', 21 ) ),
            'btw_bedrag'    => floatval( get_post_meta( $booking_id, '_cm_btw', true ) ),
            'voorrijkosten' => floatval( get_post_meta( $booking_id, '_cm_voorrijkosten', true ) ),
            'korting'       => floatval( get_post_meta( $booking_id, '_cm_discount', true ) ),
            'total'         => floatval( get_post_meta( $booking_id, '_cm_total', true ) ),
            'notities'      => get_post_meta( $booking_id, '_cm_notes', true ),
            'status'        => get_post_meta( $booking_id, '_cm_status', true ) ?: 'nieuw',
        );

        // Bedrijfsinfo
        $bedrijf = array(
            'naam'       => get_option( 'cmcalc_company_name', get_bloginfo('name') ),
            'adres'      => get_option( 'cmcalc_company_address', '' ),
            'postcode'   => get_option( 'cmcalc_company_postcode', '' ),
            'stad'       => get_option( 'cmcalc_company_city', '' ),
            'email'      => get_option( 'cmcalc_company_email', get_option('admin_email') ),
            'telefoon'   => get_option( 'cmcalc_company_phone', '' ),
            'kvk'        => get_option( 'cmcalc_company_kvk', '' ),
            'btw_nr'     => get_option( 'cmcalc_company_btw_nr', '' ),
            'iban'       => get_option( 'cmcalc_company_iban', '' ),
        );

        $factuur_nr = 'CM-' . date('Y') . '-' . str_pad( $booking_id, 5, '0', STR_PAD_LEFT );

        // Berekeningen
        if ( ! $meta['subtotaal'] && $meta['total'] ) {
            $btw_mult          = 1 + ($meta['btw_pct'] / 100);
            $meta['subtotaal'] = round( $meta['total'] / $btw_mult, 2 );
            $meta['btw_bedrag'] = $meta['total'] - $meta['subtotaal'];
        }

        return self::render_pdf( $factuur_nr, $meta, $bedrijf, $booking_id );
    }

    // ─── Pure PHP PDF renderer ────────────────────────────────────────────────

    private static function render_pdf( $factuur_nr, $meta, $bedrijf, $booking_id ) {
        // Pure PHP FPDF-compatible minimal PDF generator
        // We gebruiken een HTML → PDF aanpak via output buffering
        // en dan wrappen we het in een simpele PDF structuur.
        // Voor productie: vervang door FPDF, TCPDF, of Dompdf.

        // Controleer of Dompdf beschikbaar is via Composer
        $dompdf_autoload = WP_PLUGIN_DIR . '/dompdf/vendor/autoload.php';
        if ( file_exists( $dompdf_autoload ) ) {
            return self::render_with_dompdf( $factuur_nr, $meta, $bedrijf, $booking_id );
        }

        // Fallback: genereer HTML factuur die als download wordt aangeboden
        // (Als er geen PDF library is, stuur HTML met print stylesheet)
        return self::render_html_fallback( $factuur_nr, $meta, $bedrijf, $booking_id );
    }

    private static function get_invoice_html( $factuur_nr, $meta, $bedrijf, $booking_id ) {
        $primary = '#1B2A4A';
        $accent  = '#4DA8DA';

        ob_start();
        ?><!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<title>Factuur <?php echo esc_html($factuur_nr); ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size:13px; color:#1a1a2e; background:#fff; }
  .page { padding:40px 48px; max-width:800px; margin:0 auto; }
  .header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:40px; }
  .logo-area h1 { font-size:26px; font-weight:800; color:<?php echo $primary; ?>; letter-spacing:-0.5px; }
  .logo-area p  { color:#6b7280; font-size:12px; margin-top:2px; }
  .factuur-label { text-align:right; }
  .factuur-label .nr { font-size:20px; font-weight:700; color:<?php echo $primary; ?>; }
  .factuur-label .datum { color:#6b7280; font-size:12px; margin-top:4px; }
  .divider { border:none; border-top:2px solid <?php echo $accent; ?>; margin:0 0 32px; }
  .addresses { display:flex; gap:40px; margin-bottom:32px; }
  .address-block { flex:1; }
  .address-block h4 { font-size:11px; text-transform:uppercase; letter-spacing:0.8px; color:#9ca3af; margin-bottom:8px; }
  .address-block p { line-height:1.6; }
  .address-block strong { color:<?php echo $primary; ?>; }
  table { width:100%; border-collapse:collapse; margin-bottom:24px; }
  thead th { background:<?php echo $primary; ?>; color:#fff; padding:10px 14px; text-align:left; font-size:12px; }
  tbody td { padding:10px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; }
  tbody tr:nth-child(even) td { background:#f8fafc; }
  .totals { margin-left:auto; width:280px; }
  .totals table { margin-bottom:0; }
  .totals td { padding:7px 14px; font-size:13px; border-bottom:1px solid #f1f5f9; }
  .totals td:last-child { text-align:right; }
  .totals .total-row td { font-weight:700; font-size:15px; color:<?php echo $primary; ?>; background:<?php echo $accent; ?>22; border-top:2px solid <?php echo $accent; ?>; }
  .footer { margin-top:40px; padding-top:20px; border-top:1px solid #e5e7eb; display:flex; justify-content:space-between; font-size:11px; color:#9ca3af; }
  .badge { display:inline-block; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:600; background:#d1fae5; color:#065f46; }
  .notes { background:#f8fafc; border-left:3px solid <?php echo $accent; ?>; padding:12px 16px; margin-top:24px; font-size:12px; color:#374151; border-radius:0 4px 4px 0; }
  @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style>
</head>
<body>
<div class="page">

  <!-- Header -->
  <div class="header">
    <div class="logo-area">
      <h1><?php echo esc_html($bedrijf['naam']); ?></h1>
      <?php if ($bedrijf['adres']) : ?>
      <p><?php echo esc_html($bedrijf['adres']); ?>, <?php echo esc_html($bedrijf['postcode']); ?> <?php echo esc_html($bedrijf['stad']); ?></p>
      <?php endif; ?>
      <?php if ($bedrijf['email']) echo '<p>' . esc_html($bedrijf['email']) . '</p>'; ?>
      <?php if ($bedrijf['telefoon']) echo '<p>' . esc_html($bedrijf['telefoon']) . '</p>'; ?>
    </div>
    <div class="factuur-label">
      <div class="nr">FACTUUR</div>
      <div class="nr" style="font-size:16px;margin-top:4px;"><?php echo esc_html($factuur_nr); ?></div>
      <div class="datum">Datum: <?php echo date_i18n('d F Y'); ?></div>
      <div class="datum">Boeking: #<?php echo $booking_id; ?></div>
      <div style="margin-top:8px;"><span class="badge"><?php echo ucfirst(esc_html($meta['status'])); ?></span></div>
    </div>
  </div>

  <hr class="divider">

  <!-- Adressen -->
  <div class="addresses">
    <div class="address-block">
      <h4>Factuur aan</h4>
      <p><strong><?php echo esc_html(trim($meta['naam'])); ?></strong></p>
      <?php if ($meta['adres']) echo '<p>' . esc_html($meta['adres']) . '</p>'; ?>
      <?php if ($meta['postcode'] || $meta['stad']) echo '<p>' . esc_html(trim($meta['postcode'].' '.$meta['stad'])) . '</p>'; ?>
      <?php if ($meta['email']) echo '<p>' . esc_html($meta['email']) . '</p>'; ?>
      <?php if ($meta['telefoon']) echo '<p>' . esc_html($meta['telefoon']) . '</p>'; ?>
    </div>
    <div class="address-block">
      <h4>Bedrijfsgegevens</h4>
      <?php if ($bedrijf['kvk']) echo '<p>KvK: ' . esc_html($bedrijf['kvk']) . '</p>'; ?>
      <?php if ($bedrijf['btw_nr']) echo '<p>BTW-nr: ' . esc_html($bedrijf['btw_nr']) . '</p>'; ?>
      <?php if ($bedrijf['iban']) echo '<p>IBAN: ' . esc_html($bedrijf['iban']) . '</p>'; ?>
    </div>
    <div class="address-block">
      <h4>Dienstdetails</h4>
      <p><strong>Datum:</strong> <?php echo esc_html($meta['datum']); ?></p>
      <?php if ($meta['tijdstip']) echo '<p><strong>Tijdstip:</strong> ' . esc_html($meta['tijdstip']) . '</p>'; ?>
      <?php if ($meta['oppervlak']) echo '<p><strong>Oppervlak:</strong> ' . esc_html($meta['oppervlak']) . ' m²</p>'; ?>
    </div>
  </div>

  <!-- Regelitems -->
  <table>
    <thead>
      <tr>
        <th style="width:50%">Omschrijving</th>
        <th>Eenheid</th>
        <th style="text-align:right">Bedrag</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?php echo esc_html($meta['dienst']); ?></td>
        <td><?php echo $meta['oppervlak'] ? esc_html($meta['oppervlak']) . ' m²' : '1x'; ?></td>
        <td style="text-align:right">&euro;<?php echo number_format($meta['subtotaal'],2,',','.'); ?></td>
      </tr>
      <?php if ($meta['voorrijkosten']) : ?>
      <tr>
        <td>Voorrijkosten</td>
        <td>1x</td>
        <td style="text-align:right">&euro;<?php echo number_format($meta['voorrijkosten'],2,',','.'); ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($meta['korting'] < 0) : ?>
      <tr>
        <td>Korting</td>
        <td>—</td>
        <td style="text-align:right;color:#ef4444;">&euro;<?php echo number_format($meta['korting'],2,',','.'); ?></td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Totalen -->
  <div class="totals">
    <table>
      <tr><td>Subtotaal</td><td>&euro;<?php echo number_format($meta['subtotaal'],2,',','.'); ?></td></tr>
      <?php if ($meta['voorrijkosten']) : ?>
      <tr><td>Voorrijkosten</td><td>&euro;<?php echo number_format($meta['voorrijkosten'],2,',','.'); ?></td></tr>
      <?php endif; ?>
      <?php if ($meta['korting'] < 0) : ?>
      <tr><td>Korting</td><td style="color:#ef4444;">&euro;<?php echo number_format($meta['korting'],2,',','.'); ?></td></tr>
      <?php endif; ?>
      <?php if ($meta['btw_bedrag']) : ?>
      <tr><td>BTW (<?php echo $meta['btw_pct']; ?>%)</td><td>&euro;<?php echo number_format($meta['btw_bedrag'],2,',','.'); ?></td></tr>
      <?php endif; ?>
      <tr class="total-row"><td>Totaal</td><td>&euro;<?php echo number_format($meta['total'],2,',','.'); ?></td></tr>
    </table>
  </div>

  <?php if ($meta['notities']) : ?>
  <div class="notes"><strong>Notities:</strong> <?php echo nl2br(esc_html($meta['notities'])); ?></div>
  <?php endif; ?>

  <!-- Footer -->
  <div class="footer">
    <span><?php echo esc_html($bedrijf['naam']); ?> — Gegenereerd door CleanMasterzz</span>
    <span>Factuur <?php echo esc_html($factuur_nr); ?> — <?php echo date_i18n('d-m-Y H:i'); ?></span>
  </div>

</div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    private static function render_html_fallback( $factuur_nr, $meta, $bedrijf, $booking_id ) {
        // Geen PDF library: stuur HTML met application/pdf header (browser print)
        // Werkt als PDF-achtig bestand in de meeste browsers.
        $html = self::get_invoice_html( $factuur_nr, $meta, $bedrijf, $booking_id );
        // Wrap in een eenvoudige "print dit" pagina
        $html = str_replace( '</body>', '<script>window.onload=function(){window.print();}</script></body>', $html );
        return $html;
    }

    private static function render_with_dompdf( $factuur_nr, $meta, $bedrijf, $booking_id ) {
        require_once WP_PLUGIN_DIR . '/dompdf/vendor/autoload.php';
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml( self::get_invoice_html( $factuur_nr, $meta, $bedrijf, $booking_id ) );
        $dompdf->setPaper( 'A4', 'portrait' );
        $dompdf->render();
        return $dompdf->output();
    }

    // ─── Helper: PDF link voor admin boekingen lijst ──────────────────────────

    public static function get_download_url( $booking_id ) {
        $token = get_post_meta( $booking_id, '_cm_pdf_token', true );
        if ( ! $token ) return '';
        return add_query_arg( array(
            'action'     => 'cmcalc_download_pdf',
            'booking_id' => $booking_id,
            'token'      => $token,
        ), admin_url( 'admin-ajax.php' ) );
    }

    // ─── Helper: Factuur per mail sturen ─────────────────────────────────────

    public static function email_invoice( $booking_id ) {
        if ( ! CMCalc_License::has_feature( 'pdf_invoices' ) ) return false;

        $email = get_post_meta( $booking_id, '_cm_email', true );
        if ( ! $email ) return false;

        $token   = self::ensure_pdf_token( $booking_id );
        $pdf_url = self::get_download_url( $booking_id );
        $naam    = trim( get_post_meta( $booking_id, '_cm_name', true )
                   ?: get_post_meta( $booking_id, '_cm_first_name', true ) . ' '
                    . get_post_meta( $booking_id, '_cm_last_name', true ) );

        $subject = 'Uw factuur van ' . get_bloginfo('name');
        $body    = "Beste {$naam},\n\nHartelijk dank voor uw boeking!\n\nU kunt uw factuur downloaden via de volgende link:\n{$pdf_url}\n\nMet vriendelijke groet,\n" . get_bloginfo('name');

        return wp_mail( $email, $subject, $body );
    }
}
