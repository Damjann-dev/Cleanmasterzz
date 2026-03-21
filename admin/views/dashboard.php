<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$active_tab = sanitize_text_field( $_GET['tab'] ?? 'diensten' );

// Stats
$bedrijven_count   = CMCalc_Admin::get_bedrijf_count();
$diensten_count    = count( CMCalc_Admin::get_diensten() );
$active_diensten   = 0;
foreach ( CMCalc_Admin::get_diensten() as $d ) {
    $a = get_post_meta( $d->ID, '_cm_active', true );
    $pu = get_post_meta( $d->ID, '_cm_price_unit', true );
    if ( ($a === '1' || $a === '') && $pu !== 'km' ) $active_diensten++;
}
$werkgebieden_count = count( CMCalc_Admin::get_werkgebieden() );
$boekingen_count    = wp_count_posts( 'boeking' )->publish ?? 0;
$travel             = CMCalc_Admin::get_travel_service();
$travel_price       = $travel ? get_post_meta( $travel->ID, '_cm_base_price', true ) : '0.50';
?>
<div class="wrap cmcalc-dashboard">

    <?php if ( isset( $_GET['wizard'] ) && $_GET['wizard'] === 'complete' ) : ?>
    <div class="notice notice-success is-dismissible" style="margin:10px 0 20px;">
        <p><strong>Setup voltooid!</strong> Uw bedrijf, werkgebieden en diensten zijn succesvol geconfigureerd.</p>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="cmcalc-header">
        <div class="cmcalc-header-content">
            <div class="cmcalc-header-left">
                <div class="cmcalc-logo">
                    <svg width="42" height="42" viewBox="0 0 42 42" fill="none">
                        <rect width="42" height="42" rx="12" fill="url(#cmcLogo1)"/>
                        <rect x="1" y="1" width="40" height="40" rx="11" stroke="rgba(255,255,255,0.15)" stroke-width="1"/>
                        <path d="M12 21l5 5 10-12" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <defs>
                            <linearGradient id="cmcLogo1" x1="0" y1="0" x2="42" y2="42">
                                <stop stop-color="rgba(255,255,255,0.2)"/>
                                <stop offset="1" stop-color="rgba(255,255,255,0.05)"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
                <div>
                    <h1>CleanMasterzz Dashboard</h1>
                    <p class="cmcalc-header-sub">Beheer uw bedrijven, diensten, werkgebieden en boekingen</p>
                </div>
            </div>
            <div class="cmcalc-header-right">
                <span class="cmcalc-version">v<?php echo CMCALC_VERSION; ?></span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="cmcalc-stats-row">
        <div class="cmcalc-stat-card">
            <div class="cmcalc-stat-icon" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </div>
            <div class="cmcalc-stat-body">
                <span class="cmcalc-stat-value"><?php echo $bedrijven_count; ?></span>
                <span class="cmcalc-stat-label">Bedrijven</span>
            </div>
        </div>
        <div class="cmcalc-stat-card">
            <div class="cmcalc-stat-icon" style="background: linear-gradient(135deg, #0f172a, #334155);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </div>
            <div class="cmcalc-stat-body">
                <span class="cmcalc-stat-value"><?php echo $active_diensten; ?></span>
                <span class="cmcalc-stat-label">Actieve diensten</span>
            </div>
        </div>
        <div class="cmcalc-stat-card">
            <div class="cmcalc-stat-icon" style="background: linear-gradient(135deg, #10b981, #06b6d4);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="cmcalc-stat-body">
                <span class="cmcalc-stat-value"><?php echo $werkgebieden_count; ?></span>
                <span class="cmcalc-stat-label">Werkgebieden</span>
            </div>
        </div>
        <div class="cmcalc-stat-card">
            <div class="cmcalc-stat-icon" style="background: linear-gradient(135deg, #3b82f6, #6366f1);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="cmcalc-stat-body">
                <span class="cmcalc-stat-value"><?php echo $boekingen_count; ?></span>
                <span class="cmcalc-stat-label">Boekingen</span>
            </div>
        </div>
        <div class="cmcalc-stat-card">
            <div class="cmcalc-stat-icon" style="background: linear-gradient(135deg, #f59e0b, #ec4899);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            </div>
            <div class="cmcalc-stat-body">
                <span class="cmcalc-stat-value">&euro;<?php echo number_format( floatval( $travel_price ), 2, ',', '.' ); ?></span>
                <span class="cmcalc-stat-label">Per km toeslag</span>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="cmcalc-nav">
        <a href="?page=cmcalc-dashboard&tab=bedrijven" class="cmcalc-nav-item <?php echo $active_tab === 'bedrijven' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>Bedrijven</span>
            <span class="cmcalc-nav-badge"><?php echo $bedrijven_count; ?></span>
        </a>
        <a href="?page=cmcalc-dashboard&tab=diensten" class="cmcalc-nav-item <?php echo $active_tab === 'diensten' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            <span>Diensten</span>
            <span class="cmcalc-nav-badge"><?php echo $active_diensten; ?></span>
        </a>
        <a href="?page=cmcalc-dashboard&tab=werkgebieden" class="cmcalc-nav-item <?php echo $active_tab === 'werkgebieden' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span>Werkgebieden</span>
            <span class="cmcalc-nav-badge"><?php echo $werkgebieden_count; ?></span>
        </a>
        <a href="?page=cmcalc-dashboard&tab=instellingen" class="cmcalc-nav-item <?php echo $active_tab === 'instellingen' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>Instellingen</span>
        </a>
        <a href="?page=cmcalc-dashboard&tab=stijl" class="cmcalc-nav-item <?php echo $active_tab === 'stijl' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.555C21.965 6.012 17.461 2 12 2z"/><circle cx="7.5" cy="11.5" r="1.5"/><circle cx="10.5" cy="7.5" r="1.5"/><circle cx="15.5" cy="7.5" r="1.5"/><circle cx="17.5" cy="11.5" r="1.5"/></svg>
            <span>Stijl</span>
        </a>
        <a href="?page=cmcalc-dashboard&tab=boekingen" class="cmcalc-nav-item <?php echo $active_tab === 'boekingen' ? 'active' : ''; ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <span>Boekingen</span>
            <?php if ( $boekingen_count > 0 ) : ?>
            <span class="cmcalc-nav-badge cmcalc-nav-badge-accent"><?php echo $boekingen_count; ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Tab Content -->
    <div class="cmcalc-tab-content">
        <?php
        switch ( $active_tab ) {
            case 'bedrijven':
                include CMCALC_PLUGIN_DIR . 'admin/views/tab-bedrijven.php';
                break;
            case 'werkgebieden':
                include CMCALC_PLUGIN_DIR . 'admin/views/tab-werkgebieden.php';
                break;
            case 'instellingen':
                include CMCALC_PLUGIN_DIR . 'admin/views/tab-instellingen.php';
                break;
            case 'stijl':
                include CMCALC_PLUGIN_DIR . 'admin/views/tab-stijl.php';
                break;
            case 'boekingen':
                include CMCALC_PLUGIN_DIR . 'admin/views/tab-boekingen.php';
                break;
            default:
                include CMCALC_PLUGIN_DIR . 'admin/views/tab-diensten.php';
                break;
        }
        ?>
    </div>
</div>
