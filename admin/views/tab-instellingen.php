<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings = CMCalc_Admin::get_settings();
$travel = CMCalc_Admin::get_travel_service();
$travel_price = $travel ? get_post_meta( $travel->ID, '_cm_base_price', true ) : '0.50';
?>

<div class="cmcalc-settings-grid">
    <div class="cmcalc-settings-card">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #fd7e14, #e83e8c);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <h3>Voorrijkosten</h3>
        <p class="description">Prijs per km buiten gratis bereik. Gratis km stelt u per werkgebied in.</p>

        <div class="cmcalc-settings-field">
            <label for="cmcalcTravelPrice">Prijs per km</label>
            <div class="cmcalc-input-group">
                <span class="cmcalc-input-prefix">&euro;</span>
                <input type="number" id="cmcalcTravelPrice" value="<?php echo esc_attr( $travel_price ); ?>" step="0.01" min="0" style="width: 80px;">
                <span class="cmcalc-input-suffix">/ km</span>
            </div>
        </div>

        <button type="button" class="button cmcalc-btn-primary" id="cmcalcSaveTravelPrice" style="margin-top: 18px;">
            Opslaan
        </button>
        <span class="cmcalc-save-status" id="cmcalcTravelStatus"></span>
    </div>

    <div class="cmcalc-settings-card">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #1B2A4A, #4DA8DA);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </div>
        <h3>Standaard diensten</h3>
        <p class="description">Maak de 12 standaard diensten + voorrijkosten aan. Bestaande worden niet overschreven.</p>
        <button type="button" class="button" id="cmcalcSeedDiensten" style="margin-top: 12px;">
            Standaard diensten aanmaken
        </button>
        <span class="cmcalc-save-status" id="cmcalcSeedStatus"></span>
    </div>

    <div class="cmcalc-settings-card">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </div>
        <h3>Shortcode</h3>
        <p class="description">Plaats de calculator op elke pagina met:</p>
        <code style="margin-top: 12px;">[prijscalculator]</code>
    </div>

    <div class="cmcalc-settings-card" style="grid-column: 1 / -1;">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #7c3aed, #3b82f6);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </div>
        <h3>Teksten aanpassen</h3>
        <p class="description">Pas de teksten van de calculator aan naar wens.</p>

        <div class="cmcalc-settings-fields-grid">
            <div class="cmcalc-settings-field">
                <label for="cmcalcCalcTitle">Calculator titel</label>
                <input type="text" id="cmcalcCalcTitle" value="<?php echo esc_attr( $settings['calc_title'] ?? '' ); ?>" class="regular-text">
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcBtnStep1">Knoptekst stap 1</label>
                <input type="text" id="cmcalcBtnStep1" value="<?php echo esc_attr( $settings['btn_step1'] ?? '' ); ?>" class="regular-text">
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcBtnStep2">Knoptekst stap 2</label>
                <input type="text" id="cmcalcBtnStep2" value="<?php echo esc_attr( $settings['btn_step2'] ?? '' ); ?>" class="regular-text">
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcBtnStep3">Knoptekst stap 3</label>
                <input type="text" id="cmcalcBtnStep3" value="<?php echo esc_attr( $settings['btn_step3'] ?? '' ); ?>" class="regular-text">
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcDisclaimerText">Disclaimer tekst</label>
                <textarea id="cmcalcDisclaimerText" rows="3" class="large-text"><?php echo esc_attr( $settings['disclaimer_text'] ?? '' ); ?></textarea>
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcSuccessText">Succes melding tekst</label>
                <textarea id="cmcalcSuccessText" rows="3" class="large-text"><?php echo esc_attr( $settings['success_text'] ?? '' ); ?></textarea>
            </div>
        </div>

        <button type="button" class="button cmcalc-btn-primary" id="cmcalcSaveTexts" style="margin-top: 18px;">
            Opslaan
        </button>
        <span class="cmcalc-save-status" id="cmcalcTextsStatus"></span>
    </div>

    <div class="cmcalc-settings-card">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #0d9488, #06b6d4);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <h3>E-mail instellingen</h3>
        <p class="description">Stel het e-mailadres en onderwerp in voor notificaties.</p>

        <div class="cmcalc-settings-field">
            <label for="cmcalcAdminEmail">Admin notificatie e-mail</label>
            <input type="email" id="cmcalcAdminEmail" value="<?php echo esc_attr( $settings['admin_email'] ?? '' ); ?>" class="regular-text">
        </div>

        <div class="cmcalc-settings-field">
            <label for="cmcalcEmailSubject">E-mail onderwerp</label>
            <input type="text" id="cmcalcEmailSubject" value="<?php echo esc_attr( $settings['email_subject'] ?? '' ); ?>" class="regular-text">
        </div>

        <button type="button" class="button cmcalc-btn-primary" id="cmcalcSaveEmail" style="margin-top: 18px;">
            Opslaan
        </button>
        <span class="cmcalc-save-status" id="cmcalcEmailStatus"></span>
    </div>

    <div class="cmcalc-settings-card">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #f59e0b, #ea580c);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <h3>BTW instellingen</h3>
        <p class="description">Configureer het BTW percentage en de weergave van prijzen.</p>

        <div class="cmcalc-settings-field">
            <label for="cmcalcBtwPercentage">BTW percentage</label>
            <div class="cmcalc-input-group">
                <input type="number" id="cmcalcBtwPercentage" value="<?php echo esc_attr( $settings['btw_percentage'] ?? '21' ); ?>" step="0.1" min="0" max="100" style="width: 80px;">
                <span class="cmcalc-input-suffix">%</span>
            </div>
        </div>

        <div class="cmcalc-settings-field">
            <label for="cmcalcShowBtw">Prijzen tonen</label>
            <select id="cmcalcShowBtw">
                <option value="incl" <?php selected( $settings['show_btw'] ?? 'incl', 'incl' ); ?>>Inclusief BTW</option>
                <option value="excl" <?php selected( $settings['show_btw'] ?? 'incl', 'excl' ); ?>>Exclusief BTW</option>
            </select>
        </div>

        <button type="button" class="button cmcalc-btn-primary" id="cmcalcSaveBtw" style="margin-top: 18px;">
            Opslaan
        </button>
        <span class="cmcalc-save-status" id="cmcalcBtwStatus"></span>
    </div>
</div>

<style>
.cmcalc-settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.cmcalc-settings-grid .cmcalc-settings-card {
    max-width: none;
    padding-top: 24px;
}
.cmcalc-settings-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
}
.cmcalc-settings-fields-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.cmcalc-settings-fields-grid .cmcalc-settings-field {
    margin-top: 0;
}
</style>
