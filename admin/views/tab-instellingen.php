<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$active_sub = sanitize_text_field( $_GET['sub'] ?? 'plugin' );
?>

<!-- Sub-tab Navigatie -->
<div class="cmcalc-sub-nav">
    <a href="?page=cmcalc-dashboard&tab=instellingen&sub=plugin"
       class="cmcalc-sub-nav-item <?php echo $active_sub === 'plugin' ? 'active' : ''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Plugin Instellingen
    </a>
    <a href="?page=cmcalc-dashboard&tab=instellingen&sub=werk"
       class="cmcalc-sub-nav-item <?php echo $active_sub === 'werk' ? 'active' : ''; ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        Werkbenodigheden
    </a>
</div>

<?php if ( $active_sub === 'plugin' ) : ?>
    <?php include CMCALC_PLUGIN_DIR . 'admin/views/tab-instellingen-plugin.php'; ?>
<?php else : ?>
    <?php include CMCALC_PLUGIN_DIR . 'admin/views/tab-instellingen-werk.php'; ?>
<?php endif; ?>

<style>
.cmcalc-sub-nav {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    background: #f0f4f8;
    padding: 6px;
    border-radius: 12px;
    width: fit-content;
}
.cmcalc-sub-nav-item {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 9px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    transition: all 0.2s;
}
.cmcalc-sub-nav-item:hover {
    color: #1B2A4A;
    background: rgba(255,255,255,0.7);
}
.cmcalc-sub-nav-item.active {
    background: #fff;
    color: #1B2A4A;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
}
</style>
