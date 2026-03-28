<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! CMCalc_License::has_feature( 'boss_portal' ) ) {
    echo CMCalc_License::gate_html( 'Klantberichten', 'boss' );
    return;
}

global $wpdb;
$table_accounts  = $wpdb->prefix . 'cmcalc_accounts';
$table_messages  = $wpdb->prefix . 'cmcalc_messages';

// Admin reply actie
if ( isset( $_POST['cmcalc_reply'] ) && check_admin_referer( 'cmcalc_reply_message' ) ) {
    $account_id = intval( $_POST['account_id'] );
    $body       = sanitize_textarea_field( $_POST['reply_body'] );
    if ( $body && $account_id ) {
        $wpdb->insert( $table_messages, array(
            'account_id'  => $account_id,
            'sender'      => 'admin',
            'subject'     => 'Reactie van Cleanmasterzz',
            'body'        => $body,
            'is_read'     => 1,
            'created_at'  => current_time( 'mysql' ),
        ) );
        $account = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_accounts WHERE id = %d", $account_id ) );
        if ( $account ) {
            wp_mail( $account->email, 'Reactie van Cleanmasterzz', $body );
        }
        echo '<div class="notice notice-success"><p>Reactie verzonden.</p></div>';
    }
}

// Haal conversaties op
$accounts = $wpdb->get_results( "
    SELECT a.*, COUNT(m.id) as msg_count, SUM(m.is_read = 0 AND m.sender = 'klant') as unread
    FROM $table_accounts a
    LEFT JOIN $table_messages m ON m.account_id = a.id
    GROUP BY a.id
    ORDER BY unread DESC, a.created_at DESC
" );

$selected_id = isset( $_GET['account'] ) ? intval( $_GET['account'] ) : 0;
$messages    = array();
if ( $selected_id ) {
    $messages = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table_messages WHERE account_id = %d ORDER BY created_at ASC",
        $selected_id
    ) );
    // Markeer als gelezen
    $wpdb->update( $table_messages, array( 'is_read' => 1 ), array( 'account_id' => $selected_id, 'sender' => 'klant' ) );
}
?>
<style>
.berichten-wrap { display: grid; grid-template-columns: 280px 1fr; gap: 20px; }
.berichten-list { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
.berichten-list-item { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; cursor: pointer; text-decoration: none; display: block; color: inherit; }
.berichten-list-item:hover, .berichten-list-item.active { background: #f0f7ff; }
.berichten-list-item .naam { font-weight: 600; font-size: 13px; }
.berichten-list-item .email { font-size: 11px; color: #94a3b8; }
.berichten-list-item .unread { display: inline-block; background: #3b82f6; color: #fff; border-radius: 10px; font-size: 10px; padding: 1px 7px; margin-left: 6px; }
.berichten-thread { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; display: flex; flex-direction: column; gap: 12px; }
.msg-bubble { max-width: 70%; padding: 12px 16px; border-radius: 12px; font-size: 13px; line-height: 1.5; }
.msg-klant { background: #f1f5f9; align-self: flex-start; }
.msg-admin { background: #1B2A4A; color: #fff; align-self: flex-end; }
.msg-meta { font-size: 11px; color: #94a3b8; margin-top: 4px; }
.reply-form { margin-top: 20px; border-top: 1px solid #f1f5f9; padding-top: 20px; }
.reply-form textarea { width: 100%; padding: 10px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; resize: vertical; }
</style>

<h2 style="margin-bottom:20px;">Klantberichten</h2>

<?php if ( empty( $accounts ) ) : ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:48px;text-align:center;color:#64748b;">
    Nog geen klantaccounts aangemaakt.
</div>
<?php else : ?>
<div class="berichten-wrap">
    <div class="berichten-list">
        <?php foreach ( $accounts as $acc ) : ?>
        <a href="?page=cmcalc-dashboard&tab=berichten&account=<?php echo $acc->id; ?>"
           class="berichten-list-item <?php echo $selected_id === $acc->id ? 'active' : ''; ?>">
            <div class="naam">
                <?php echo esc_html( $acc->first_name . ' ' . $acc->last_name ); ?>
                <?php if ( $acc->unread > 0 ) : ?>
                <span class="unread"><?php echo intval( $acc->unread ); ?></span>
                <?php endif; ?>
            </div>
            <div class="email"><?php echo esc_html( $acc->email ); ?></div>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="berichten-thread">
        <?php if ( ! $selected_id ) : ?>
        <p style="color:#94a3b8;text-align:center;margin:auto;">Selecteer een klant om berichten te bekijken.</p>
        <?php elseif ( empty( $messages ) ) : ?>
        <p style="color:#94a3b8;">Nog geen berichten.</p>
        <?php else : ?>
            <?php foreach ( $messages as $msg ) : ?>
            <div>
                <div class="msg-bubble <?php echo $msg->sender === 'admin' ? 'msg-admin' : 'msg-klant'; ?>">
                    <?php echo nl2br( esc_html( $msg->body ) ); ?>
                </div>
                <div class="msg-meta" style="<?php echo $msg->sender === 'admin' ? 'text-align:right;' : ''; ?>">
                    <?php echo esc_html( date( 'd-m-Y H:i', strtotime( $msg->created_at ) ) ); ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="reply-form">
                <form method="post">
                    <?php wp_nonce_field( 'cmcalc_reply_message' ); ?>
                    <input type="hidden" name="account_id" value="<?php echo $selected_id; ?>">
                    <textarea name="reply_body" rows="3" placeholder="Typ uw reactie..."></textarea>
                    <button type="submit" name="cmcalc_reply" class="button button-primary" style="margin-top:8px;">Versturen</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
