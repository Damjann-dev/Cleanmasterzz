<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings     = CMCalc_Admin::get_settings();
$travel       = CMCalc_Admin::get_travel_service();
$travel_price = $travel ? get_post_meta( $travel->ID, '_cm_base_price', true ) : '0.50';
?>

<div class="cmcalc-settings-grid">

    <!-- ── Voorrijkosten ─────────────────────────────────────────────── -->
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

    <!-- ── Standaard diensten ─────────────────────────────────────────── -->
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

    <!-- ── Shortcode ─────────────────────────────────────────────────── -->
    <div class="cmcalc-settings-card">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        </div>
        <h3>Shortcodes</h3>
        <p class="description">Plaats de onderdelen op elke WordPress pagina.</p>
        <div style="margin-top:12px;display:flex;flex-direction:column;gap:10px;">
            <div>
                <small style="color:#888;font-weight:600;display:block;margin-bottom:4px;">CALCULATOR</small>
                <code style="display:block;padding:8px 12px;background:#f8f9fa;border-radius:6px;">[prijscalculator]</code>
            </div>
            <div>
                <small style="color:#888;font-weight:600;display:block;margin-bottom:4px;">KLANTPORTAAL</small>
                <code style="display:block;padding:8px 12px;background:#f8f9fa;border-radius:6px;">[cmcalc_portal]</code>
            </div>
        </div>
    </div>

    <!-- ── Teksten aanpassen ──────────────────────────────────────────── -->
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

    <!-- ── Kortingscodes ─────────────────────────────────────────────── -->
    <div class="cmcalc-settings-card" style="grid-column: 1 / -1;">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #e91e63, #f44336);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        </div>
        <h3>Kortingscodes</h3>
        <p class="description">Maak kortingscodes aan voor klanten. Codes kunnen een percentage of vast bedrag zijn.</p>

        <div class="cmcalc-discount-form" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
            <input type="text" id="cmcalcDiscountCode" placeholder="CODE" style="width:120px;text-transform:uppercase;">
            <select id="cmcalcDiscountType">
                <option value="percentage">Percentage (%)</option>
                <option value="fixed">Vast bedrag (&euro;)</option>
            </select>
            <input type="number" id="cmcalcDiscountValue" placeholder="Waarde" step="0.01" min="0" style="width:90px;">
            <input type="number" id="cmcalcDiscountMaxUses" placeholder="Max gebruik (0=onbeperkt)" min="0" style="width:180px;">
            <input type="date" id="cmcalcDiscountExpires" title="Vervaldatum (leeg=geen)">
            <button type="button" class="button cmcalc-btn-primary" id="cmcalcAddDiscount">+ Toevoegen</button>
        </div>

        <table class="cmcalc-table" id="cmcalcDiscountTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Waarde</th>
                    <th>Gebruikt</th>
                    <th>Max</th>
                    <th>Vervalt</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody id="cmcalcDiscountBody">
                <tr><td colspan="7" style="text-align:center;color:#999;">Laden...</td></tr>
            </tbody>
        </table>
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
