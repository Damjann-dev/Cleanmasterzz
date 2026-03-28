<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$status = CMCalc_License::get_status_info();

$tier_colors = array(
    'free'   => array( 'from' => '#64748b', 'to' => '#94a3b8' ),
    'pro'    => array( 'from' => '#3b82f6', 'to' => '#6366f1' ),
    'boss'   => array( 'from' => '#f59e0b', 'to' => '#ec4899' ),
    'agency' => array( 'from' => '#10b981', 'to' => '#06b6d4' ),
);
$tc = $tier_colors[ $status['tier'] ] ?? $tier_colors['free'];

$feature_labels = array(
    'calculator'             => array( 'label' => 'Prijscalculator',            'tier' => 'free' ),
    'bookings'               => array( 'label' => 'Boekingsbeheer',             'tier' => 'free' ),
    'basic_email'            => array( 'label' => 'E-mail notificaties',        'tier' => 'free' ),
    'basic_portal'           => array( 'label' => 'Basis klantportaal',         'tier' => 'free' ),
    'discount_codes'         => array( 'label' => 'Kortingscodes',              'tier' => 'free' ),
    'single_company'         => array( 'label' => 'Enkelvoudig bedrijfsprofiel','tier' => 'free' ),
    'company_wizard'         => array( 'label' => 'Bedrijf setup wizard',       'tier' => 'pro' ),
    'analytics'              => array( 'label' => 'Analytics dashboard',        'tier' => 'pro' ),
    'pdf_invoices'           => array( 'label' => 'PDF facturen',               'tier' => 'pro' ),
    'advanced_discounts'     => array( 'label' => 'Geavanceerde kortingen',     'tier' => 'pro' ),
    'calendar'               => array( 'label' => 'Kalender & beschikbaarheid', 'tier' => 'pro' ),
    'multi_company'          => array( 'label' => 'Multi-bedrijf beheer',       'tier' => 'pro' ),
    'boss_portal'            => array( 'label' => 'Boss klantportaal',          'tier' => 'boss' ),
    'customer_accounts'      => array( 'label' => 'Klantaccounts & login',      'tier' => 'boss' ),
    'customer_messages'      => array( 'label' => 'Berichtensysteem',           'tier' => 'boss' ),
    'sms_notifications'      => array( 'label' => 'SMS notificaties',           'tier' => 'boss' ),
    'company_employee_login' => array( 'label' => 'Medewerker logins',          'tier' => 'boss' ),
    'white_label'            => array( 'label' => 'White-label',                'tier' => 'agency' ),
    'reseller_dashboard'     => array( 'label' => 'Reseller dashboard',         'tier' => 'agency' ),
    'unlimited_companies'    => array( 'label' => 'Onbeperkt bedrijven',        'tier' => 'agency' ),
);
?>

<div class="cmcalc-license-wrap">

    <!-- ── Huidige status kaart ───────────────────────────────────────── -->
    <div class="cmcalc-license-status-card" style="background: linear-gradient(135deg, <?php echo esc_attr($tc['from']); ?>, <?php echo esc_attr($tc['to']); ?>);">
        <div class="cmcalc-license-status-left">
            <div class="cmcalc-license-badge">
                <?php if ( $status['tier'] === 'free' ) : ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <?php else : ?>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <?php endif; ?>
            </div>
            <div>
                <div class="cmcalc-license-tier"><?php echo esc_html( $status['tier_label'] ); ?></div>
                <div class="cmcalc-license-domain"><?php echo esc_html( $status['domain'] ); ?></div>
            </div>
        </div>
        <div class="cmcalc-license-status-right">
            <?php if ( $status['valid'] ) : ?>
                <span class="cmcalc-license-pill cmcalc-license-pill--active">Actief</span>
                <?php if ( $status['expires_at'] ) : ?>
                    <div class="cmcalc-license-expires">Verloopt: <?php echo esc_html( date( 'd-m-Y', strtotime( $status['expires_at'] ) ) ); ?></div>
                <?php else : ?>
                    <div class="cmcalc-license-expires">Lifetime</div>
                <?php endif; ?>
            <?php elseif ( $status['in_grace'] ) : ?>
                <span class="cmcalc-license-pill cmcalc-license-pill--grace">Grace Period</span>
                <div class="cmcalc-license-expires">Server tijdelijk niet bereikbaar</div>
            <?php else : ?>
                <span class="cmcalc-license-pill cmcalc-license-pill--inactive">
                    <?php echo $status['has_key'] ? 'Verlopen / Ongeldig' : 'Geen licentie'; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="cmcalc-license-grid">

        <!-- ── Licentie activeren ───────────────────────────────────── -->
        <div class="cmcalc-settings-card">
            <h3 style="margin-top:0;margin-bottom:6px;">
                <?php echo $status['has_key'] ? 'Licentie beheren' : 'Licentie activeren'; ?>
            </h3>
            <p class="description" style="margin-bottom:18px;">
                <?php if ( $status['has_key'] ) : ?>
                    Huidige sleutel: <code><?php echo esc_html( $status['masked_key'] ); ?></code><br>
                    Laatste controle: <?php echo esc_html( $status['last_checked'] ); ?>
                <?php else : ?>
                    Voer uw licentiesleutel in om premium functies te activeren.
                <?php endif; ?>
            </p>

            <div class="cmcalc-settings-field">
                <label for="cmcalcLicenseKey">Licentiesleutel</label>
                <input type="text" id="cmcalcLicenseKey" class="regular-text"
                       placeholder="CMCALC-XXXX-XXXX-XXXX-XXXX"
                       style="font-family:monospace;letter-spacing:1px;text-transform:uppercase;"
                       <?php echo $status['has_key'] ? 'disabled' : ''; ?>>
            </div>

            <div style="display:flex;gap:10px;margin-top:14px;flex-wrap:wrap;">
                <?php if ( ! $status['has_key'] ) : ?>
                    <button type="button" class="button cmcalc-btn-primary" id="cmcalcActivateLicense">
                        Activeren
                    </button>
                <?php else : ?>
                    <button type="button" class="button" id="cmcalcRefreshLicense">
                        🔄 Status vernieuwen
                    </button>
                    <button type="button" class="button" id="cmcalcDeactivateLicense"
                            style="color:#dc3545;border-color:#dc3545;"
                            onclick="return confirm('Weet u zeker dat u de licentie wilt deactiveren? Premium functies worden uitgeschakeld.')">
                        Deactiveren
                    </button>
                <?php endif; ?>
                <span class="cmcalc-save-status" id="cmcalcLicenseStatus"></span>
            </div>
        </div>

        <!-- ── Feature overzicht ────────────────────────────────────── -->
        <div class="cmcalc-settings-card">
            <h3 style="margin-top:0;margin-bottom:14px;">Beschikbare functies</h3>
            <div class="cmcalc-feature-list">
                <?php
                $tier_order = array( 'free', 'pro', 'boss', 'agency' );
                $current_tier_idx = array_search( $status['tier'], $tier_order );
                $last_tier_shown = '';

                foreach ( $feature_labels as $feature_key => $feature_info ) :
                    $feat_tier     = $feature_info['tier'];
                    $feat_tier_idx = array_search( $feat_tier, $tier_order );
                    $available     = $current_tier_idx >= $feat_tier_idx;

                    if ( $feat_tier !== $last_tier_shown ) :
                        $tier_name_map = array( 'free' => 'Gratis', 'pro' => 'Professional', 'boss' => 'Boss', 'agency' => 'Agency' );
                        $last_tier_shown = $feat_tier;
                        ?>
                        <div class="cmcalc-feature-tier-label"><?php echo esc_html( $tier_name_map[$feat_tier] ); ?></div>
                    <?php endif; ?>

                    <div class="cmcalc-feature-item <?php echo $available ? 'available' : 'locked'; ?>">
                        <?php if ( $available ) : ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php else : ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <?php endif; ?>
                        <span><?php echo esc_html( $feature_info['label'] ); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- ── Upgrade tabel ──────────────────────────────────────────────── -->
    <?php if ( $status['tier'] !== 'agency' ) : ?>
    <div class="cmcalc-settings-card" style="margin-top:24px;">
        <h3 style="margin-top:0;margin-bottom:4px;">Upgrade uw licentie</h3>
        <p class="description" style="margin-bottom:24px;">Kies het pakket dat bij uw ambities past.</p>

        <div class="cmcalc-pricing-grid">

            <?php if ( $status['tier'] !== 'pro' && $status['tier'] !== 'boss' ) : ?>
            <div class="cmcalc-pricing-card">
                <div class="cmcalc-pricing-tier" style="background:linear-gradient(135deg,#3b82f6,#6366f1);">Professional</div>
                <div class="cmcalc-pricing-price">€29<small>/mnd</small></div>
                <div class="cmcalc-pricing-alt">of €249/jaar</div>
                <ul class="cmcalc-pricing-features">
                    <li>Bedrijf setup wizard</li>
                    <li>Analytics dashboard</li>
                    <li>PDF facturen</li>
                    <li>Geavanceerde kortingen</li>
                    <li>Kalender & beschikbaarheid</li>
                    <li>Multi-bedrijf beheer</li>
                </ul>
                <a href="#" class="cmcalc-pricing-btn cmcalc-pricing-btn--pro" onclick="document.getElementById('cmcalcLicenseKey').focus();return false;">
                    Key invoeren
                </a>
            </div>
            <?php endif; ?>

            <?php if ( $status['tier'] !== 'boss' ) : ?>
            <div class="cmcalc-pricing-card cmcalc-pricing-card--featured">
                <div class="cmcalc-pricing-popular">Meest gekozen</div>
                <div class="cmcalc-pricing-tier" style="background:linear-gradient(135deg,#f59e0b,#ec4899);">Boss</div>
                <div class="cmcalc-pricing-price">€59<small>/mnd</small></div>
                <div class="cmcalc-pricing-alt">of €499/jaar</div>
                <ul class="cmcalc-pricing-features">
                    <li>Alles van Professional</li>
                    <li>Boss klantportaal + login</li>
                    <li>Klantaccounts dashboard</li>
                    <li>Berichtensysteem</li>
                    <li>SMS notificaties</li>
                    <li>Medewerker logins</li>
                </ul>
                <a href="#" class="cmcalc-pricing-btn cmcalc-pricing-btn--boss" onclick="document.getElementById('cmcalcLicenseKey').focus();return false;">
                    Key invoeren
                </a>
            </div>
            <?php endif; ?>

            <div class="cmcalc-pricing-card">
                <div class="cmcalc-pricing-tier" style="background:linear-gradient(135deg,#10b981,#06b6d4);">Agency</div>
                <div class="cmcalc-pricing-price">€149<small>/mnd</small></div>
                <div class="cmcalc-pricing-alt">of €1.199/jaar</div>
                <ul class="cmcalc-pricing-features">
                    <li>Alles van Boss</li>
                    <li>White-label</li>
                    <li>Reseller dashboard</li>
                    <li>Onbeperkt bedrijven</li>
                    <li>Prioriteit support</li>
                </ul>
                <a href="#" class="cmcalc-pricing-btn cmcalc-pricing-btn--agency" onclick="document.getElementById('cmcalcLicenseKey').focus();return false;">
                    Key invoeren
                </a>
            </div>

        </div>

        <p style="margin-top:16px;font-size:12px;color:#94a3b8;text-align:center;">
            Licenties worden aangemaakt door de plugin eigenaar. Neem contact op voor een key.
        </p>
    </div>
    <?php endif; ?>

</div>

<style>
.cmcalc-license-wrap { max-width: 900px; }

/* Status kaart */
.cmcalc-license-status-card {
    border-radius: 16px;
    padding: 28px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    color: #fff;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
}
.cmcalc-license-status-left { display: flex; align-items: center; gap: 16px; }
.cmcalc-license-badge {
    width: 52px; height: 52px;
    background: rgba(255,255,255,0.2);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
}
.cmcalc-license-tier { font-size: 22px; font-weight: 800; }
.cmcalc-license-domain { font-size: 13px; opacity: 0.75; margin-top: 2px; }
.cmcalc-license-status-right { text-align: right; }
.cmcalc-license-expires { font-size: 12px; opacity: 0.75; margin-top: 6px; }
.cmcalc-license-pill {
    display: inline-block;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}
.cmcalc-license-pill--active   { background: rgba(255,255,255,0.25); }
.cmcalc-license-pill--grace    { background: rgba(251,191,36,0.35); }
.cmcalc-license-pill--inactive { background: rgba(239,68,68,0.3); }

/* License grid */
.cmcalc-license-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
@media (max-width: 700px) { .cmcalc-license-grid { grid-template-columns: 1fr; } }

/* Feature list */
.cmcalc-feature-list { display: flex; flex-direction: column; gap: 2px; }
.cmcalc-feature-tier-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;
    color: #94a3b8; margin: 10px 0 4px; padding-left: 4px;
}
.cmcalc-feature-item {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 8px; border-radius: 6px; font-size: 13px;
    transition: background 0.15s;
}
.cmcalc-feature-item.available { color: #1B2A4A; }
.cmcalc-feature-item.locked    { color: #94a3b8; }
.cmcalc-feature-item:hover     { background: #f8fafc; }

/* Pricing grid */
.cmcalc-pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}
.cmcalc-pricing-card {
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 24px;
    position: relative;
    transition: box-shadow 0.2s;
}
.cmcalc-pricing-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.cmcalc-pricing-card--featured {
    border-color: #f59e0b;
    box-shadow: 0 4px 16px rgba(245,158,11,0.2);
}
.cmcalc-pricing-popular {
    position: absolute; top: -12px; left: 50%; transform: translateX(-50%);
    background: linear-gradient(135deg,#f59e0b,#ec4899);
    color: #fff; font-size: 11px; font-weight: 700;
    padding: 4px 14px; border-radius: 12px; white-space: nowrap;
}
.cmcalc-pricing-tier {
    color: #fff; font-weight: 700; font-size: 13px;
    padding: 5px 14px; border-radius: 8px; display: inline-block;
    margin-bottom: 14px;
}
.cmcalc-pricing-price { font-size: 32px; font-weight: 800; color: #1B2A4A; line-height: 1; }
.cmcalc-pricing-price small { font-size: 14px; font-weight: 400; color: #64748b; }
.cmcalc-pricing-alt { font-size: 12px; color: #94a3b8; margin: 4px 0 16px; }
.cmcalc-pricing-features { list-style: none; padding: 0; margin: 0 0 20px; }
.cmcalc-pricing-features li {
    font-size: 13px; color: #475569;
    padding: 5px 0;
    border-bottom: 1px solid #f1f5f9;
    padding-left: 20px;
    position: relative;
}
.cmcalc-pricing-features li::before {
    content: '✓'; position: absolute; left: 0; color: #10b981; font-weight: 700;
}
.cmcalc-pricing-btn {
    display: block; width: 100%; text-align: center;
    padding: 11px; border-radius: 10px; font-weight: 700; font-size: 13px;
    text-decoration: none; transition: opacity 0.2s;
}
.cmcalc-pricing-btn:hover { opacity: 0.85; }
.cmcalc-pricing-btn--pro    { background: linear-gradient(135deg,#3b82f6,#6366f1); color: #fff; }
.cmcalc-pricing-btn--boss   { background: linear-gradient(135deg,#f59e0b,#ec4899); color: #fff; }
.cmcalc-pricing-btn--agency { background: linear-gradient(135deg,#10b981,#06b6d4); color: #fff; }
</style>

<script>
jQuery(function($) {
    var nonce = cmcalcAdmin.nonce;

    // Activeer
    $('#cmcalcActivateLicense').on('click', function() {
        var key = $('#cmcalcLicenseKey').val().trim().toUpperCase();
        if (!key) { $('#cmcalcLicenseStatus').text('Voer een sleutel in.').css('color','#dc3545'); return; }
        var $btn = $(this).text('Activeren...').prop('disabled', true);
        $.post(cmcalcAdmin.ajaxUrl, {
            action: 'cmcalc_activate_license', nonce: nonce, license_key: key
        }).done(function(res) {
            if (res.success) {
                $('#cmcalcLicenseStatus').text(res.data.message).css('color','#10b981');
                setTimeout(function(){ location.reload(); }, 1500);
            } else {
                $('#cmcalcLicenseStatus').text(res.data).css('color','#dc3545');
                $btn.text('Activeren').prop('disabled', false);
            }
        }).fail(function() {
            $('#cmcalcLicenseStatus').text('Verbindingsfout.').css('color','#dc3545');
            $btn.text('Activeren').prop('disabled', false);
        });
    });

    // Vernieuwen
    $('#cmcalcRefreshLicense').on('click', function() {
        var $btn = $(this).text('Controleren...').prop('disabled', true);
        $.post(cmcalcAdmin.ajaxUrl, {
            action: 'cmcalc_refresh_license', nonce: nonce
        }).done(function(res) {
            if (res.success) {
                $('#cmcalcLicenseStatus').text('Status bijgewerkt.').css('color','#10b981');
                setTimeout(function(){ location.reload(); }, 1000);
            }
        }).always(function() {
            $btn.text('🔄 Status vernieuwen').prop('disabled', false);
        });
    });

    // Deactiveren
    $('#cmcalcDeactivateLicense').on('click', function() {
        var $btn = $(this).text('Deactiveren...').prop('disabled', true);
        $.post(cmcalcAdmin.ajaxUrl, {
            action: 'cmcalc_deactivate_license', nonce: nonce
        }).done(function(res) {
            if (res.success) {
                $('#cmcalcLicenseStatus').text(res.data.message).css('color','#10b981');
                setTimeout(function(){ location.reload(); }, 1000);
            }
        }).always(function() {
            $btn.text('Deactiveren').prop('disabled', false);
        });
    });

    // Auto-format key input: CMCALC-XXXX-XXXX-XXXX-XXXX
    $('#cmcalcLicenseKey').on('input', function() {
        var val = $(this).val().replace(/[^A-Za-z0-9]/g, '').toUpperCase();
        var formatted = '';
        if (val.length > 0) formatted = val.substring(0, 6);
        if (val.length > 6) formatted += '-' + val.substring(6, 10);
        if (val.length > 10) formatted += '-' + val.substring(10, 14);
        if (val.length > 14) formatted += '-' + val.substring(14, 18);
        if (val.length > 18) formatted += '-' + val.substring(18, 22);
        $(this).val(formatted);
    });
});
</script>
