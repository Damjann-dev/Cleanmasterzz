<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$settings   = CMCalc_Admin::get_settings();
$smtp       = CMCalc_SMTP::get_safe_settings();
$travel     = CMCalc_Admin::get_travel_service();
$travel_price = $travel ? get_post_meta( $travel->ID, '_cm_base_price', true ) : '0.50';

// Portaalpagina opties
$pages = get_pages();
$portal_page_id = intval( get_option( 'cmcalc_portal_page_id' ) );
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

    <div class="cmcalc-settings-card" style="grid-column: 1 / -1;">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #0d9488, #06b6d4);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        </div>
        <h3>E-mail instellingen</h3>
        <p class="description">Configureer admin- en klante-mails. Klanten ontvangen automatisch een HTML-bevestiging en statusupdates.</p>

        <div class="cmcalc-settings-fields-grid">
            <div class="cmcalc-settings-field">
                <label for="cmcalcAdminEmail">Admin notificatie e-mail</label>
                <input type="email" id="cmcalcAdminEmail" value="<?php echo esc_attr( $settings['admin_email'] ?? '' ); ?>" class="regular-text">
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcEmailSubject">E-mail onderwerp</label>
                <input type="text" id="cmcalcEmailSubject" value="<?php echo esc_attr( $settings['email_subject'] ?? '' ); ?>" class="regular-text">
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcEmailLogoUrl">Logo URL (voor in e-mails)</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="text" id="cmcalcEmailLogoUrl" value="<?php echo esc_attr( $settings['email_logo_url'] ?? '' ); ?>" class="regular-text" placeholder="https://...">
                    <button type="button" class="button" id="cmcalcEmailLogoBtn" style="white-space:nowrap;">Kies afbeelding</button>
                </div>
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcEmailFooter">Footer tekst</label>
                <input type="text" id="cmcalcEmailFooter" value="<?php echo esc_attr( $settings['email_footer_text'] ?? '' ); ?>" class="regular-text" placeholder="Heeft u vragen? Neem gerust contact met ons op.">
            </div>
        </div>

        <div style="display:flex;gap:24px;margin-top:16px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;">
                <div class="cmcalc-toggle" style="margin:0;">
                    <input type="checkbox" id="cmcalcEmailCustomerEnabled" <?php checked( $settings['email_customer_enabled'] ?? '1', '1' ); ?>>
                    <span class="cmcalc-toggle-slider"></span>
                </div>
                Klantbevestiging e-mail
            </label>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;">
                <div class="cmcalc-toggle" style="margin:0;">
                    <input type="checkbox" id="cmcalcEmailStatusEnabled" <?php checked( $settings['email_status_enabled'] ?? '1', '1' ); ?>>
                    <span class="cmcalc-toggle-slider"></span>
                </div>
                Statusupdate e-mails
            </label>
        </div>

        <div style="display:flex;gap:10px;margin-top:18px;align-items:center;">
            <button type="button" class="button cmcalc-btn-primary" id="cmcalcSaveEmail">
                Opslaan
            </button>
            <button type="button" class="button" id="cmcalcPreviewEmail">
                <span class="dashicons dashicons-visibility" style="margin-top:3px;"></span> Preview e-mail
            </button>
            <span class="cmcalc-save-status" id="cmcalcEmailStatus"></span>
        </div>
    </div>

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

    <div class="cmcalc-settings-card" style="grid-column: 1 / -1;">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #25d366, #128c7e);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        </div>
        <h3>WhatsApp</h3>
        <p class="description">Laat klanten hun boeking ook via WhatsApp versturen.</p>

        <div class="cmcalc-settings-field">
            <label for="cmcalcWhatsApp">WhatsApp nummer (internationaal, bijv. 31612345678)</label>
            <input type="text" id="cmcalcWhatsApp" value="<?php echo esc_attr( $settings['whatsapp_number'] ?? '' ); ?>" class="regular-text" placeholder="31612345678">
        </div>

        <button type="button" class="button cmcalc-btn-primary" id="cmcalcSaveWhatsApp" style="margin-top: 18px;">
            Opslaan
        </button>
        <span class="cmcalc-save-status" id="cmcalcWhatsAppStatus"></span>
    </div>

    <!-- Email Preview Modal -->
    <div id="cmcalcEmailPreviewModal" class="cmcalc-modal" style="display:none;">
        <div class="cmcalc-modal-overlay"></div>
        <div class="cmcalc-modal-content" style="width:680px;max-height:90vh;">
            <div class="cmcalc-modal-header">
                <h3>E-mail preview</h3>
                <button type="button" class="cmcalc-modal-close">&times;</button>
            </div>
            <div class="cmcalc-modal-body" style="padding:0;overflow:auto;max-height:calc(90vh - 120px);">
                <iframe id="cmcalcEmailPreviewFrame" style="width:100%;min-height:600px;border:none;"></iframe>
            </div>
        </div>
    </div>

    <!-- ── SMTP ───────────────────────────────────────────────────────────── -->
    <div class="cmcalc-settings-card" style="grid-column: 1 / -1;">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #0ea5e9, #6366f1);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.27h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.7a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
        </div>
        <h3>SMTP mailserver</h3>
        <p class="description">Verzendt e-mails via uw eigen mailserver. Wachtwoord wordt versleuteld opgeslagen.</p>

        <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:600;">
                <div class="cmcalc-toggle" style="margin:0;">
                    <input type="checkbox" id="cmcalcSmtpEnabled" <?php checked( $smtp['enabled'] ); ?>>
                    <span class="cmcalc-toggle-slider"></span>
                </div>
                SMTP ingeschakeld
            </label>
            <span id="cmcalcSmtpStatus" class="cmcalc-save-status"></span>
        </div>

        <div id="cmcalcSmtpFields" class="cmcalc-settings-fields-grid" style="<?php echo $smtp['enabled'] ? '' : 'opacity:.5;pointer-events:none;'; ?>">
            <div class="cmcalc-settings-field">
                <label for="cmcalcSmtpHost">SMTP host</label>
                <input type="text" id="cmcalcSmtpHost" value="<?php echo esc_attr( $smtp['host'] ); ?>" class="regular-text" placeholder="mailout.hostnet.nl">
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcSmtpPort">Poort</label>
                <div class="cmcalc-input-group">
                    <input type="number" id="cmcalcSmtpPort" value="<?php echo esc_attr( $smtp['port'] ); ?>" style="width:90px;" min="1" max="65535">
                    <select id="cmcalcSmtpEncryption" style="margin-left:8px;">
                        <option value="tls" <?php selected( $smtp['encryption'], 'tls' ); ?>>STARTTLS (587)</option>
                        <option value="ssl" <?php selected( $smtp['encryption'], 'ssl' ); ?>>SSL/TLS (465)</option>
                        <option value=""   <?php selected( $smtp['encryption'], '' ); ?>>Geen (25)</option>
                    </select>
                </div>
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcSmtpUsername">Gebruikersnaam (e-mailadres)</label>
                <input type="text" id="cmcalcSmtpUsername" value="<?php echo esc_attr( $smtp['username'] ); ?>" class="regular-text" autocomplete="off" placeholder="info@uwbedrijf.nl">
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcSmtpPassword">Wachtwoord</label>
                <input type="password" id="cmcalcSmtpPassword" value="<?php echo esc_attr( $smtp['password'] ); ?>" class="regular-text" autocomplete="new-password" placeholder="<?php echo $smtp['password'] ? 'Huidig wachtwoord verborgen — vul in om te wijzigen' : 'Wachtwoord'; ?>">
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcSmtpFromName">Afzendernaam</label>
                <input type="text" id="cmcalcSmtpFromName" value="<?php echo esc_attr( $smtp['from_name'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
            </div>

            <div class="cmcalc-settings-field">
                <label for="cmcalcSmtpFromEmail">Afzender e-mailadres</label>
                <input type="email" id="cmcalcSmtpFromEmail" value="<?php echo esc_attr( $smtp['from_email'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
            </div>
        </div>

        <div style="display:flex;gap:10px;margin-top:18px;align-items:center;flex-wrap:wrap;">
            <button type="button" class="button cmcalc-btn-primary" id="cmcalcSaveSmtp">Opslaan</button>
            <button type="button" class="button" id="cmcalcTestSmtp">📨 Testmail versturen</button>
        </div>

        <div id="cmcalcSmtpTestResult" style="display:none;margin-top:12px;padding:10px 14px;border-radius:8px;font-size:13px;"></div>
    </div>

    <!-- ── Klantportaal ───────────────────────────────────────────────────── -->
    <div class="cmcalc-settings-card" style="grid-column: 1 / -1;">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #f59e0b, #ec4899);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <h3>Klantportaal</h3>
        <p class="description">Klanten kunnen via <code>[cmcalc_portal]</code> hun boeking bekijken. Koppel hieronder een pagina waarop die shortcode staat.</p>

        <div class="cmcalc-settings-field" style="max-width:360px;margin-top:12px;">
            <label for="cmcalcPortalPage">Portaalpagina</label>
            <select id="cmcalcPortalPage">
                <option value="">— Geen (link werkt op elke pagina) —</option>
                <?php foreach ( $pages as $page ) : ?>
                    <option value="<?php echo esc_attr( $page->ID ); ?>" <?php selected( $portal_page_id, $page->ID ); ?>>
                        <?php echo esc_html( $page->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <p class="description" style="margin-top:8px;font-size:12px;">
            Shortcode voor portaalpagina: <code>[cmcalc_portal]</code> &bull;
            Boekingslinks worden automatisch meegestuurd in bevestigingsemails.
        </p>

        <div style="display:flex;gap:10px;margin-top:18px;align-items:center;">
            <button type="button" class="button cmcalc-btn-primary" id="cmcalcSavePortalPage">Opslaan</button>
            <span class="cmcalc-save-status" id="cmcalcPortalStatus"></span>
        </div>
    </div>

    <div class="cmcalc-settings-card">
        <div class="cmcalc-settings-card-icon" style="background: linear-gradient(135deg, #333, #555);">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
        </div>
        <h3>Auto-updater</h3>
        <p class="description">GitHub token voor automatische plugin updates (vereist voor privé repos).</p>

        <div class="cmcalc-settings-field">
            <label for="cmcalcGithubToken">GitHub Token</label>
            <input type="password" id="cmcalcGithubToken" value="<?php echo esc_attr( get_option( 'cmcalc_github_token', '' ) ); ?>" class="regular-text" placeholder="ghp_... of gho_...">
        </div>

        <div style="display:flex;gap:10px;margin-top:18px;align-items:center;">
            <button type="button" class="button cmcalc-btn-primary" id="cmcalcSaveGithubToken">
                Opslaan
            </button>
            <button type="button" class="button" id="cmcalcTestUpdate">
                🔄 Check nu op updates
            </button>
            <span class="cmcalc-save-status" id="cmcalcGithubTokenStatus"></span>
        </div>

        <p class="description" style="margin-top:10px;font-size:12px;opacity:0.7;">
            Huidige versie: <strong><?php echo CMCALC_VERSION; ?></strong>
        </p>
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
