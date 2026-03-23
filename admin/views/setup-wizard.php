<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$default_diensten = CMCalc_Seeder::get_default_diensten();
$presets = CMCalc_Admin::get_style_presets();

// Remove travel service (km) from wizard selection
$wizard_diensten = array_filter( $default_diensten, function( $d ) {
    return $d['price_unit'] !== 'km';
} );

// Check if diensten already exist in DB
$existing_diensten = get_posts( array( 'post_type' => 'dienst', 'posts_per_page' => -1, 'post_status' => 'any' ) );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Calculator Setup — <?php bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
    <style>
        /* Reset WP admin bar for wizard */
        #wpadminbar { display: none !important; }
        html { margin-top: 0 !important; }

        * { box-sizing: border-box; }

        body.cmcalc-wizard-body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .cmcalc-wizard {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.2);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }

        .cmcalc-wizard-header {
            background: #1B2A4A;
            color: #fff;
            padding: 30px 40px;
            text-align: center;
        }

        .cmcalc-wizard-header h1 {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 700;
        }

        .cmcalc-wizard-header p {
            margin: 0;
            opacity: 0.7;
            font-size: 14px;
        }

        /* Progress bar */
        .cmcalc-wizard-progress {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 24px 40px 0;
        }

        .cmcalc-wizard-dot {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
        }

        .cmcalc-wizard-dot.active {
            background: #1B2A4A;
            color: #fff;
        }

        .cmcalc-wizard-dot.completed {
            background: #28a745;
            color: #fff;
        }

        .cmcalc-wizard-dot + .cmcalc-wizard-dot::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            width: 8px;
            height: 2px;
            background: #e9ecef;
        }

        .cmcalc-wizard-dot.completed + .cmcalc-wizard-dot::before,
        .cmcalc-wizard-dot.active + .cmcalc-wizard-dot::before {
            background: #28a745;
        }

        /* Steps */
        .cmcalc-wizard-body {
            padding: 30px 40px;
        }

        .cmcalc-wizard-step {
            display: none;
        }

        .cmcalc-wizard-step.active {
            display: block;
            animation: wizardFadeIn 0.3s ease;
        }

        @keyframes wizardFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .cmcalc-wizard-step h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1B2A4A;
            margin: 0 0 4px;
        }

        .cmcalc-wizard-step .step-desc {
            color: #6c757d;
            font-size: 13px;
            margin: 0 0 20px;
        }

        /* Form fields */
        .cmcalc-wiz-field {
            margin-bottom: 16px;
        }

        .cmcalc-wiz-field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .cmcalc-wiz-field input,
        .cmcalc-wiz-field select {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .cmcalc-wiz-field input:focus {
            border-color: #4DA8DA;
            outline: none;
            box-shadow: 0 0 0 3px rgba(77,168,218,0.1);
        }

        .cmcalc-wiz-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        /* Geocode button */
        .cmcalc-wiz-geocode-row {
            display: flex;
            gap: 8px;
        }

        .cmcalc-wiz-geocode-row input {
            flex: 1;
        }

        .cmcalc-wiz-geocode-btn {
            padding: 10px 16px;
            border: 1.5px solid #e9ecef;
            border-radius: 8px;
            background: #f8f9fa;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .cmcalc-wiz-geocode-btn:hover {
            border-color: #4DA8DA;
            background: #eef7fc;
        }

        .cmcalc-wiz-geocode-status {
            font-size: 12px;
            color: #28a745;
            margin-top: 4px;
            display: none;
        }

        /* Werkgebieden cards */
        .cmcalc-wiz-wg-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 16px;
        }

        .cmcalc-wiz-wg-card {
            background: #f8f9fa;
            border: 1.5px solid #e9ecef;
            border-radius: 10px;
            padding: 16px;
            position: relative;
        }

        .cmcalc-wiz-wg-card .cmcalc-wiz-row {
            grid-template-columns: 2fr 1fr 1fr;
        }

        .cmcalc-wiz-wg-remove {
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            color: #dc3545;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            padding: 4px;
        }

        .cmcalc-wiz-add-wg {
            background: none;
            border: 2px dashed #e9ecef;
            border-radius: 10px;
            padding: 14px;
            width: 100%;
            cursor: pointer;
            color: #6c757d;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .cmcalc-wiz-add-wg:hover {
            border-color: #4DA8DA;
            color: #4DA8DA;
        }

        /* Diensten table */
        .cmcalc-wiz-diensten {
            max-height: 400px;
            overflow-y: auto;
        }

        .cmcalc-wiz-dienst-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .cmcalc-wiz-dienst-row:hover {
            background: #f8f9fa;
        }

        .cmcalc-wiz-dienst-row input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #1B2A4A;
        }

        .cmcalc-wiz-dienst-info {
            flex: 1;
        }

        .cmcalc-wiz-dienst-name {
            font-weight: 600;
            font-size: 14px;
            color: #2d3436;
        }

        .cmcalc-wiz-dienst-desc {
            font-size: 12px;
            color: #6c757d;
        }

        .cmcalc-wiz-dienst-price {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
        }

        .cmcalc-wiz-dienst-price input {
            width: 70px;
            padding: 4px 8px;
            border: 1.5px solid #e9ecef;
            border-radius: 6px;
            font-size: 13px;
            text-align: right;
        }

        .cmcalc-wiz-dienst-price small {
            color: #6c757d;
        }

        /* Preset grid */
        .cmcalc-wiz-presets {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .cmcalc-wiz-preset {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 16px;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }

        .cmcalc-wiz-preset:hover {
            border-color: #4DA8DA;
        }

        .cmcalc-wiz-preset.selected {
            border-color: #1B2A4A;
            background: rgba(27,42,74,0.03);
        }

        .cmcalc-wiz-preset-colors {
            display: flex;
            gap: 4px;
            justify-content: center;
            margin-bottom: 8px;
        }

        .cmcalc-wiz-preset-swatch {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid rgba(0,0,0,0.05);
        }

        .cmcalc-wiz-preset-name {
            font-size: 13px;
            font-weight: 600;
            color: #2d3436;
        }

        /* Color Preview */
        .cmcalc-color-preview {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            margin-top: 16px;
            max-width: 300px;
        }
        .cmcalc-color-preview__header {
            padding: 16px;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
        }
        .cmcalc-color-preview__card {
            padding: 16px;
            background: #fff;
        }
        .cmcalc-color-preview__service {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 13px;
        }
        .cmcalc-color-preview__dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .cmcalc-color-preview__price {
            margin-left: auto;
            font-weight: 700;
        }
        .cmcalc-color-preview__btn {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }

        /* Footer */
        .cmcalc-wizard-footer {
            display: flex;
            justify-content: space-between;
            padding: 0 40px 30px;
        }

        .cmcalc-wiz-btn {
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .cmcalc-wiz-btn-back {
            background: #f8f9fa;
            color: #6c757d;
            border: 1.5px solid #e9ecef;
        }

        .cmcalc-wiz-btn-back:hover {
            background: #e9ecef;
        }

        .cmcalc-wiz-btn-next {
            background: #1B2A4A;
            color: #fff;
        }

        .cmcalc-wiz-btn-next:hover {
            opacity: 0.9;
        }

        .cmcalc-wiz-btn-next:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .cmcalc-wiz-skip {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 13px;
            text-decoration: underline;
        }

        /* Quote label */
        .cmcalc-wiz-quote-label {
            background: rgba(77,168,218,0.1);
            color: #2b7bb9;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        /* Info box */
        .cmcalc-wiz-info-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 13px;
            color: #1e40af;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        .cmcalc-wiz-info-box svg { flex-shrink: 0; margin-top: 1px; }

        /* Premium grid step 7 */
        .cmcalc-wiz-premium-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 4px;
        }
        .cmcalc-wiz-premium-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 18px;
            position: relative;
        }
        .cmcalc-wiz-premium-card--boss {
            border-color: #f59e0b;
        }
        .cmcalc-wiz-premium-popular {
            position: absolute; top: -10px; left: 50%; transform: translateX(-50%);
            background: linear-gradient(135deg,#f59e0b,#ec4899);
            color: #fff; font-size: 10px; font-weight: 700;
            padding: 3px 12px; border-radius: 10px; white-space: nowrap;
        }
        .cmcalc-wiz-premium-badge {
            display: inline-block;
            font-size: 10px; font-weight: 800; letter-spacing: 1px;
            padding: 3px 10px; border-radius: 6px; margin-bottom: 8px;
        }
        .cmcalc-wiz-premium-card--pro .cmcalc-wiz-premium-badge {
            background: linear-gradient(135deg,#3b82f6,#6366f1); color: #fff;
        }
        .cmcalc-wiz-premium-card--boss .cmcalc-wiz-premium-badge {
            background: linear-gradient(135deg,#f59e0b,#ec4899); color: #fff;
        }
        .cmcalc-wiz-premium-card h4 {
            margin: 0 0 2px; font-size: 15px; color: #1B2A4A;
        }
        .cmcalc-wiz-premium-card > p {
            font-size: 12px; color: #6c757d; margin: 0 0 10px;
        }
        .cmcalc-wiz-premium-card ul {
            list-style: none; padding: 0; margin: 0;
            font-size: 12px; color: #475569;
        }
        .cmcalc-wiz-premium-card ul li {
            padding: 3px 0 3px 18px; position: relative;
        }
        .cmcalc-wiz-premium-card ul li::before {
            content: '✓'; position: absolute; left: 0; color: #10b981; font-weight: 700;
        }
    </style>
</head>
<body class="cmcalc-wizard-body">

<div class="cmcalc-wizard">
    <div class="cmcalc-wizard-header">
        <h1>Cleanmasterzz Calculator Setup</h1>
        <p>Configureer uw calculator in een paar eenvoudige stappen</p>
    </div>

    <div class="cmcalc-wizard-progress">
        <span class="cmcalc-wizard-dot active" data-step="1" title="Bedrijfsgegevens">1</span>
        <span class="cmcalc-wizard-dot" data-step="2" title="Werkgebieden">2</span>
        <span class="cmcalc-wizard-dot" data-step="3" title="Diensten">3</span>
        <span class="cmcalc-wizard-dot" data-step="4" title="E-mail">4</span>
        <span class="cmcalc-wizard-dot" data-step="5" title="BTW & Voorrijkosten">5</span>
        <span class="cmcalc-wizard-dot" data-step="6" title="Stijl">6</span>
        <span class="cmcalc-wizard-dot" data-step="7" title="Premium">7</span>
    </div>

    <div class="cmcalc-wizard-body">
        <!-- Step 1: Bedrijfsgegevens -->
        <div class="cmcalc-wizard-step active" data-step="1">
            <h2>Bedrijfsgegevens</h2>
            <p class="step-desc">Voer de gegevens van uw bedrijf in</p>

            <div class="cmcalc-wiz-field">
                <label>Bedrijfsnaam *</label>
                <input type="text" id="wizBedrijfName" placeholder="Bijv. Cleanmasterzz Breda" required>
            </div>

            <div class="cmcalc-wiz-field">
                <label>Adres</label>
                <input type="text" id="wizBedrijfAddress" placeholder="Straat en huisnummer">
            </div>

            <div class="cmcalc-wiz-field">
                <label>Postcode *</label>
                <div class="cmcalc-wiz-geocode-row">
                    <input type="text" id="wizBedrijfPostcode" placeholder="1234AB" maxlength="7">
                    <input type="text" id="wizBedrijfHuisnummer" placeholder="Nr." style="width:70px;">
                    <button type="button" class="cmcalc-wiz-geocode-btn" id="wizGeocodeBedrijf" title="Locatie ophalen">
                        📍
                    </button>
                </div>
                <div class="cmcalc-wiz-geocode-status" id="wizGeocodeStatus"></div>
            </div>

            <input type="hidden" id="wizBedrijfLat" value="0">
            <input type="hidden" id="wizBedrijfLon" value="0">

            <div class="cmcalc-wiz-row">
                <div class="cmcalc-wiz-field">
                    <label>Telefoon</label>
                    <input type="tel" id="wizBedrijfPhone" placeholder="06-12345678">
                </div>
                <div class="cmcalc-wiz-field">
                    <label>Email</label>
                    <input type="email" id="wizBedrijfEmail" placeholder="info@bedrijf.nl" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
                </div>
            </div>
        </div>

        <!-- Step 2: Werkgebieden -->
        <div class="cmcalc-wizard-step" data-step="2">
            <h2>Werkgebieden</h2>
            <p class="step-desc">Bepaal in welke regio's uw bedrijf actief is en hoeveel gratis kilometers u aanbiedt</p>

            <div class="cmcalc-wiz-wg-list" id="wizWerkgebiedenList">
                <!-- Pre-filled with company postcode on step entry -->
            </div>

            <button type="button" class="cmcalc-wiz-add-wg" id="wizAddWerkgebied">
                + Werkgebied toevoegen
            </button>
        </div>

        <!-- Step 3: Diensten -->
        <div class="cmcalc-wizard-step" data-step="3">
            <h2>Diensten selecteren</h2>
            <p class="step-desc">Kies welke diensten uw bedrijf aanbiedt en pas eventueel de prijzen aan</p>

            <div style="margin-bottom:12px;">
                <label style="font-size:13px;cursor:pointer;">
                    <input type="checkbox" id="wizSelectAll" checked style="margin-right:6px;"> Alles selecteren
                </label>
            </div>

            <div class="cmcalc-wiz-diensten" id="wizDienstenList">
                <?php
                $unit_labels = array( 'm2' => '/m²', 'stuk' => '/stuk', 'paneel' => '/paneel', 'raam' => '/raam', 'vast' => 'vast' );
                foreach ( $wizard_diensten as $dienst ) :
                    // Check if exists in DB
                    $db_id = 0;
                    foreach ( $existing_diensten as $ed ) {
                        if ( $ed->post_title === $dienst['title'] ) { $db_id = $ed->ID; break; }
                    }
                    $is_quote = $dienst['requires_quote'];
                ?>
                <div class="cmcalc-wiz-dienst-row" data-db-id="<?php echo $db_id; ?>">
                    <input type="checkbox" class="cmcalc-wiz-dienst-check" checked
                           data-title="<?php echo esc_attr( $dienst['title'] ); ?>"
                           data-unit="<?php echo esc_attr( $dienst['price_unit'] ); ?>"
                           data-quote="<?php echo $is_quote ? '1' : '0'; ?>">
                    <div class="cmcalc-wiz-dienst-info">
                        <div class="cmcalc-wiz-dienst-name"><?php echo esc_html( $dienst['title'] ); ?></div>
                        <div class="cmcalc-wiz-dienst-desc"><?php echo esc_html( $dienst['excerpt'] ); ?></div>
                    </div>
                    <?php if ( $is_quote ) : ?>
                        <span class="cmcalc-wiz-quote-label">Offerte</span>
                    <?php else : ?>
                        <div class="cmcalc-wiz-dienst-price">
                            <span>€</span>
                            <input type="number" step="0.01" min="0" class="cmcalc-wiz-dienst-base-price"
                                   value="<?php echo esc_attr( $dienst['base_price'] ); ?>">
                            <small><?php echo $unit_labels[ $dienst['price_unit'] ] ?? ''; ?></small>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Step 4: E-mail -->
        <div class="cmcalc-wizard-step" data-step="4">
            <h2>E-mail instellingen</h2>
            <p class="step-desc">Stel in naar welk adres boekingen worden gestuurd en of klanten een bevestiging ontvangen.</p>

            <div class="cmcalc-wiz-field">
                <label>Admin notificatie e-mail</label>
                <input type="email" id="wizAdminEmail" placeholder="info@uwbedrijf.nl" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
            </div>

            <div class="cmcalc-wiz-field">
                <label>E-mail onderwerp</label>
                <input type="text" id="wizEmailSubject" placeholder="Nieuwe boeking via uw website" value="Nieuwe boeking">
            </div>

            <div style="display:flex;flex-direction:column;gap:10px;margin-top:4px;">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px;border:1.5px solid #e9ecef;border-radius:8px;">
                    <input type="checkbox" id="wizEmailCustomer" checked style="width:18px;height:18px;accent-color:#1B2A4A;">
                    <div>
                        <div style="font-weight:600;font-size:13px;">Klantbevestiging sturen</div>
                        <div style="font-size:12px;color:#6c757d;">Klant ontvangt automatisch een HTML-bevestiging</div>
                    </div>
                </label>
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px;border:1.5px solid #e9ecef;border-radius:8px;">
                    <input type="checkbox" id="wizEmailStatus" checked style="width:18px;height:18px;accent-color:#1B2A4A;">
                    <div>
                        <div style="font-weight:600;font-size:13px;">Statusupdate e-mails</div>
                        <div style="font-size:12px;color:#6c757d;">Klant ontvangt e-mail bij statuswijziging</div>
                    </div>
                </label>
            </div>

            <div class="cmcalc-wiz-info-box" style="margin-top:16px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                SMTP configuratie (eigen mailserver) kunt u later instellen onder <strong>Instellingen → Plugin Instellingen</strong>.
            </div>
        </div>

        <!-- Step 5: BTW & Voorrijkosten -->
        <div class="cmcalc-wizard-step" data-step="5">
            <h2>BTW & Voorrijkosten</h2>
            <p class="step-desc">Stel de BTW in en de reiskosten buiten het gratis bereik.</p>

            <div class="cmcalc-wiz-row">
                <div class="cmcalc-wiz-field">
                    <label>BTW percentage (%)</label>
                    <input type="number" id="wizBtwPercentage" value="21" step="0.1" min="0" max="100">
                </div>
                <div class="cmcalc-wiz-field">
                    <label>Prijs per km (buiten gratis bereik)</label>
                    <input type="number" id="wizTravelPrice" value="0.50" step="0.01" min="0" placeholder="€ per km">
                </div>
            </div>

            <div class="cmcalc-wiz-field" style="margin-top:8px;">
                <label>Prijzen tonen als</label>
                <select id="wizShowBtw">
                    <option value="incl">Inclusief BTW</option>
                    <option value="excl">Exclusief BTW</option>
                </select>
            </div>

            <div class="cmcalc-wiz-info-box" style="margin-top:16px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Het standaard BTW-percentage in Nederland is <strong>21%</strong> voor de meeste schoonmaakdiensten.
                Bij particulieren geldt soms <strong>9%</strong> voor specifieke diensten.
            </div>
        </div>

        <!-- Step 6: Stijl -->
        <div class="cmcalc-wizard-step" data-step="6">
            <h2>Kies een stijl</h2>
            <p class="step-desc">Selecteer een kleurthema voor uw calculator. U kunt dit later altijd aanpassen.</p>

            <div class="cmcalc-wiz-presets" id="wizPresets">
                <?php foreach ( $presets as $key => $preset ) : ?>
                <div class="cmcalc-wiz-preset <?php echo $key === 'default' ? 'selected' : ''; ?>" data-preset="<?php echo esc_attr( $key ); ?>"
                     data-primary="<?php echo esc_attr( $preset['primary_color'] ); ?>"
                     data-secondary="<?php echo esc_attr( $preset['secondary_color'] ); ?>">
                    <div class="cmcalc-wiz-preset-colors">
                        <span class="cmcalc-wiz-preset-swatch" style="background:<?php echo esc_attr( $preset['primary_color'] ); ?>;"></span>
                        <span class="cmcalc-wiz-preset-swatch" style="background:<?php echo esc_attr( $preset['secondary_color'] ); ?>;"></span>
                        <span class="cmcalc-wiz-preset-swatch" style="background:<?php echo esc_attr( $preset['accent_color'] ); ?>;"></span>
                    </div>
                    <div class="cmcalc-wiz-preset-name"><?php
                        $preset_names = array( 'default' => 'Standaard', 'dark' => 'Donker', 'light' => 'Licht & Fris', 'nature' => 'Natuur', 'warm' => 'Warm' );
                        echo $preset_names[ $key ] ?? ucfirst( $key );
                    ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cmcalc-color-preview" id="cmcalcColorPreview">
                <div class="cmcalc-color-preview__header" id="cmcalcPreviewHeader" style="background:#1B2A4A;">
                    <span>Voorbeeld</span>
                </div>
                <div class="cmcalc-color-preview__card" id="cmcalcPreviewCard">
                    <div class="cmcalc-color-preview__service">
                        <span class="cmcalc-color-preview__dot" id="cmcalcPreviewDot" style="background:#1B2A4A;"></span>
                        <span>Glasbewassing</span>
                        <span class="cmcalc-color-preview__price" id="cmcalcPreviewPrice" style="color:#1B2A4A;">&euro;3,50</span>
                    </div>
                    <button class="cmcalc-color-preview__btn" id="cmcalcPreviewBtn" style="background:#1B2A4A;">Boek nu</button>
                </div>
            </div>
        </div>

        <!-- Step 7: Premium -->
        <div class="cmcalc-wizard-step" data-step="7">
            <h2>🎉 Setup bijna klaar!</h2>
            <p class="step-desc">Uw calculator is geconfigureerd. Bekijk hieronder de premium functies die u kunt ontgrendelen.</p>

            <div class="cmcalc-wiz-premium-grid">
                <div class="cmcalc-wiz-premium-card cmcalc-wiz-premium-card--pro">
                    <div class="cmcalc-wiz-premium-badge">PRO</div>
                    <h4>Professional</h4>
                    <p>€29/mnd of €249/jaar</p>
                    <ul>
                        <li>Bedrijf setup wizard</li>
                        <li>Analytics dashboard</li>
                        <li>PDF facturen</li>
                        <li>Kalender & beschikbaarheid</li>
                    </ul>
                </div>
                <div class="cmcalc-wiz-premium-card cmcalc-wiz-premium-card--boss">
                    <div class="cmcalc-wiz-premium-popular">Meest gekozen</div>
                    <div class="cmcalc-wiz-premium-badge">BOSS</div>
                    <h4>Boss</h4>
                    <p>€59/mnd of €499/jaar</p>
                    <ul>
                        <li>Alles van Pro</li>
                        <li>Boss klantportaal + login</li>
                        <li>Klantaccounts dashboard</li>
                        <li>SMS notificaties</li>
                        <li>Berichtensysteem</li>
                    </ul>
                </div>
            </div>

            <div class="cmcalc-wiz-info-box" style="margin-top:20px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Heeft u al een licentiesleutel? U kunt deze activeren via <strong>Dashboard → Licentie</strong> na het voltooien van de setup.
            </div>
        </div>
    </div>

    <div class="cmcalc-wizard-footer">
        <button type="button" class="cmcalc-wiz-btn cmcalc-wiz-btn-back" id="wizBack" style="visibility:hidden;">Terug</button>
        <div>
            <a href="<?php echo admin_url( 'admin.php?page=cmcalc-dashboard' ); ?>" class="cmcalc-wiz-skip">Overslaan</a>
            <button type="button" class="cmcalc-wiz-btn cmcalc-wiz-btn-next" id="wizNext">Volgende</button>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    var currentStep = 1;
    var totalSteps = 7;
    var ajaxUrl = '<?php echo admin_url( "admin-ajax.php" ); ?>';
    var nonce = '<?php echo wp_create_nonce( "cmcalc_admin_nonce" ); ?>';

    function goToStep(step) {
        currentStep = step;
        $('.cmcalc-wizard-step').removeClass('active');
        $('.cmcalc-wizard-step[data-step="' + step + '"]').addClass('active');

        // Update dots
        $('.cmcalc-wizard-dot').each(function() {
            var dotStep = $(this).data('step');
            $(this).removeClass('active completed');
            if (dotStep < step) $(this).addClass('completed');
            if (dotStep === step) $(this).addClass('active');
        });

        // Back button
        $('#wizBack').css('visibility', step > 1 ? 'visible' : 'hidden');

        // Next button text
        if (step === totalSteps) {
            $('#wizNext').text('Voltooien');
        } else {
            $('#wizNext').text('Volgende');
        }

        // Pre-fill werkgebied on step 2 if empty
        if (step === 2 && $('#wizWerkgebiedenList').children().length === 0) {
            addWerkgebiedCard($('#wizBedrijfPostcode').val(), '', $('#wizBedrijfLat').val(), $('#wizBedrijfLon').val());
        }
    }

    // Navigation
    $('#wizNext').on('click', function() {
        if (currentStep === 1) {
            // Validate
            if (!$('#wizBedrijfName').val().trim()) {
                $('#wizBedrijfName').css('border-color', '#dc3545').focus();
                return;
            }
            if (!$('#wizBedrijfPostcode').val().trim()) {
                $('#wizBedrijfPostcode').css('border-color', '#dc3545').focus();
                return;
            }
            goToStep(2);
        } else if (currentStep < totalSteps) {
            goToStep(currentStep + 1);
        } else {
            submitWizard();
        }
    });

    $('#wizBack').on('click', function() {
        if (currentStep > 1) goToStep(currentStep - 1);
    });

    // Geocode bedrijf
    $('#wizGeocodeBedrijf').on('click', function() {
        var pc = $('#wizBedrijfPostcode').val().trim();
        if (!pc) return;
        var $btn = $(this);
        $btn.text('...').prop('disabled', true);

        $.get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free', {
            q: pc, rows: 1, fq: 'type:postcode'
        }).done(function(data) {
            var docs = data.response && data.response.docs;
            if (docs && docs.length > 0) {
                var centroid = docs[0].centroide_ll || '';
                var m = centroid.match(/POINT\(([0-9.]+)\s+([0-9.]+)\)/);
                if (m) {
                    $('#wizBedrijfLat').val(m[2]);
                    $('#wizBedrijfLon').val(m[1]);
                    var city = docs[0].woonplaatsnaam || '';
                    $('#wizGeocodeStatus').text('Locatie gevonden: ' + city + ' (' + parseFloat(m[2]).toFixed(4) + ', ' + parseFloat(m[1]).toFixed(4) + ')').show();
                }
            }
        }).always(function() {
            $btn.text('📍').prop('disabled', false);
        });
    });

    // Werkgebieden
    function addWerkgebiedCard(postcode, name, lat, lon) {
        var html = '<div class="cmcalc-wiz-wg-card">' +
            '<button type="button" class="cmcalc-wiz-wg-remove">&times;</button>' +
            '<div class="cmcalc-wiz-row">' +
                '<div class="cmcalc-wiz-field"><label>Naam</label><input type="text" class="wiz-wg-name" placeholder="Bijv. Breda" value="' + (name || '') + '"></div>' +
                '<div class="cmcalc-wiz-field"><label>Postcode</label>' +
                    '<div class="cmcalc-wiz-geocode-row"><input type="text" class="wiz-wg-postcode" value="' + (postcode || '') + '" maxlength="7">' +
                    '<button type="button" class="cmcalc-wiz-geocode-btn wiz-wg-geocode">📍</button></div>' +
                '</div>' +
                '<div class="cmcalc-wiz-field"><label>Gratis km</label><input type="number" class="wiz-wg-freekm" value="20" min="0"></div>' +
            '</div>' +
            '<input type="hidden" class="wiz-wg-lat" value="' + (lat || 0) + '">' +
            '<input type="hidden" class="wiz-wg-lon" value="' + (lon || 0) + '">' +
        '</div>';
        $('#wizWerkgebiedenList').append(html);
    }

    $('#wizAddWerkgebied').on('click', function() {
        addWerkgebiedCard('', '', 0, 0);
    });

    $(document).on('click', '.cmcalc-wiz-wg-remove', function() {
        $(this).closest('.cmcalc-wiz-wg-card').remove();
    });

    $(document).on('click', '.wiz-wg-geocode', function() {
        var $card = $(this).closest('.cmcalc-wiz-wg-card');
        var pc = $card.find('.wiz-wg-postcode').val().trim();
        if (!pc) return;
        var $btn = $(this);
        $btn.text('...').prop('disabled', true);

        $.get('https://api.pdok.nl/bzk/locatieserver/search/v3_1/free', {
            q: pc, rows: 1, fq: 'type:postcode'
        }).done(function(data) {
            var docs = data.response && data.response.docs;
            if (docs && docs.length > 0) {
                var centroid = docs[0].centroide_ll || '';
                var m = centroid.match(/POINT\(([0-9.]+)\s+([0-9.]+)\)/);
                if (m) {
                    $card.find('.wiz-wg-lat').val(m[2]);
                    $card.find('.wiz-wg-lon').val(m[1]);
                    if (!$card.find('.wiz-wg-name').val()) {
                        $card.find('.wiz-wg-name').val(docs[0].woonplaatsnaam || '');
                    }
                }
            }
        }).always(function() {
            $btn.text('📍').prop('disabled', false);
        });
    });

    // Select all diensten
    $('#wizSelectAll').on('change', function() {
        $('.cmcalc-wiz-dienst-check').prop('checked', this.checked);
    });

    // Color preview update
    function updateColorPreview(primary, secondary) {
        $('#cmcalcPreviewHeader').css('background', primary);
        $('#cmcalcPreviewDot').css('background', primary);
        $('#cmcalcPreviewPrice').css('color', primary);
        $('#cmcalcPreviewBtn').css('background', primary);
    }

    // Presets
    $(document).on('click', '.cmcalc-wiz-preset', function() {
        $('.cmcalc-wiz-preset').removeClass('selected');
        $(this).addClass('selected');
        var primary = $(this).data('primary');
        var secondary = $(this).data('secondary');
        if (primary) updateColorPreview(primary, secondary);
    });

    // Submit wizard
    function submitWizard() {
        var $btn = $('#wizNext');
        $btn.text('Bezig...').prop('disabled', true);

        // Collect data
        var werkgebieden = [];
        $('#wizWerkgebiedenList .cmcalc-wiz-wg-card').each(function() {
            var $c = $(this);
            werkgebieden.push({
                name: $c.find('.wiz-wg-name').val(),
                postcode: $c.find('.wiz-wg-postcode').val(),
                lat: parseFloat($c.find('.wiz-wg-lat').val()) || 0,
                lon: parseFloat($c.find('.wiz-wg-lon').val()) || 0,
                free_km: parseInt($c.find('.wiz-wg-freekm').val()) || 20
            });
        });

        var diensten = [];
        $('.cmcalc-wiz-dienst-row').each(function() {
            var $r = $(this);
            var $check = $r.find('.cmcalc-wiz-dienst-check');
            if (!$check.prop('checked')) return;
            diensten.push({
                id: parseInt($r.data('db-id')) || 0,
                title: $check.data('title'),
                base_price: parseFloat($r.find('.cmcalc-wiz-dienst-base-price').val()) || 0,
                unit: $check.data('unit'),
                requires_quote: $check.data('quote') === 1
            });
        });

        var preset = $('.cmcalc-wiz-preset.selected').data('preset') || 'default';

        var wizardData = {
            bedrijf: {
                name: $('#wizBedrijfName').val(),
                address: $('#wizBedrijfAddress').val(),
                postcode: $('#wizBedrijfPostcode').val(),
                huisnummer: $('#wizBedrijfHuisnummer').val(),
                phone: $('#wizBedrijfPhone').val(),
                email: $('#wizBedrijfEmail').val(),
                lat: parseFloat($('#wizBedrijfLat').val()) || 0,
                lon: parseFloat($('#wizBedrijfLon').val()) || 0
            },
            werkgebieden: werkgebieden,
            diensten: diensten,
            preset: preset,
            email: {
                admin_email:            $('#wizAdminEmail').val(),
                email_subject:          $('#wizEmailSubject').val(),
                email_customer_enabled: $('#wizEmailCustomer').is(':checked') ? '1' : '0',
                email_status_enabled:   $('#wizEmailStatus').is(':checked') ? '1' : '0'
            },
            btw: {
                btw_percentage: parseFloat($('#wizBtwPercentage').val()) || 21,
                show_btw:       $('#wizShowBtw').val() || 'incl',
                travel_price:   parseFloat($('#wizTravelPrice').val()) || 0.50
            }
        };

        $.post(ajaxUrl, {
            action: 'cmcalc_wizard_complete',
            nonce: nonce,
            wizard_data: JSON.stringify(wizardData)
        }).done(function(res) {
            if (res.success) {
                window.location.href = '<?php echo admin_url( "admin.php?page=cmcalc-dashboard&wizard=complete" ); ?>';
            } else {
                alert('Er is een fout opgetreden: ' + (res.data || 'Onbekend'));
                $btn.text('Voltooien').prop('disabled', false);
            }
        }).fail(function() {
            alert('Verbindingsfout. Probeer het opnieuw.');
            $btn.text('Voltooien').prop('disabled', false);
        });
    }

    // Clear validation styling on input
    $('input').on('input', function() {
        $(this).css('border-color', '');
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
