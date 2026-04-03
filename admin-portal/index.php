<?php
/**
 * CleanMasterzz Admin Portal
 * portal.cleanmasterzz.nl — Standalone beheerdersdashboard
 */

require_once __DIR__ . '/config.php';

session_name( PORTAL_SESSION );
session_start();

// ─── Auth ─────────────────────────────────────────────────────────────────────
function is_logged_in() { return ! empty( $_SESSION['cm_portal_admin'] ); }
function require_login() {
    if ( ! is_logged_in() ) { header( 'Location: /?action=login' ); exit; }
}

// ─── WordPress API helper ──────────────────────────────────────────────────────
function wp_api( $endpoint, $method = 'GET', $body = null ) {
    $url = rtrim( WP_SITE_URL, '/' ) . '/wp-json/cleanmasterzz/v1/admin/' . ltrim( $endpoint, '/' );
    $opts = array(
        'http' => array(
            'method'  => $method,
            'header'  => array(
                'Authorization: Basic ' . base64_encode( WP_API_USER . ':' . WP_API_PASS ),
                'Content-Type: application/json',
                'Accept: application/json',
            ),
            'timeout'         => 15,
            'ignore_errors'   => true,
        ),
    );
    if ( $body !== null ) {
        $opts['http']['content'] = json_encode( $body );
    }
    $ctx  = stream_context_create( $opts );
    $raw  = @file_get_contents( $url, false, $ctx );
    if ( $raw === false ) return null;
    return json_decode( $raw, true );
}

// ─── Actions ──────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'dashboard';
$msg = $err = '';

if ( $action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ( $user === PORTAL_USERNAME && password_verify( $pass, PORTAL_PASSWORD_HASH ) ) {
        $_SESSION['cm_portal_admin'] = true;
        header( 'Location: /' ); exit;
    }
    $err = 'Onjuiste inloggegevens.';
}

if ( $action === 'logout' ) {
    session_destroy();
    header( 'Location: /?action=login' ); exit;
}

if ( $action !== 'login' ) require_login();

// Update booking status via API
if ( $action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $id     = intval( $_POST['id'] ?? 0 );
    $status = $_POST['status'] ?? '';
    $result = wp_api( "bookings/{$id}/status", 'POST', array( 'status' => $status ) );
    $msg    = $result && ! empty( $result['success'] ) ? 'Status bijgewerkt.' : 'Bijwerken mislukt.';
    header( 'Location: /?action=bookings&msg=' . urlencode( $msg ) ); exit;
}

// Bulk status update
if ( $action === 'bulk_status' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $ids    = array_map( 'intval', $_POST['ids'] ?? array() );
    $status = $_POST['status'] ?? '';
    $result = wp_api( 'bookings/bulk-status', 'POST', array( 'ids' => $ids, 'status' => $status ) );
    $msg    = $result ? ( $result['updated'] ?? 0 ) . ' boekingen bijgewerkt.' : 'Bijwerken mislukt.';
    header( 'Location: /?action=bookings&msg=' . urlencode( $msg ) ); exit;
}

// Reply to message
if ( $action === 'reply_message' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $account_id = intval( $_POST['account_id'] ?? 0 );
    $body       = trim( $_POST['body'] ?? '' );
    $subject    = trim( $_POST['subject'] ?? 'Antwoord van CleanMasterzz' );
    $result     = wp_api( "messages/{$account_id}/reply", 'POST', array( 'body' => $body, 'subject' => $subject ) );
    $msg        = $result && ! empty( $result['success'] ) ? 'Antwoord verzonden.' : 'Verzenden mislukt.';
    header( 'Location: /?action=messages&msg=' . urlencode( $msg ) ); exit;
}

// ─── Data laden ───────────────────────────────────────────────────────────────
$stats     = null;
$bookings  = null;
$messages  = null;
$customers = null;
$revenue   = null;
$booking   = null;

if ( is_logged_in() ) {
    if ( $action === 'dashboard' )  $stats    = wp_api( 'stats' );
    if ( $action === 'bookings' || $action === 'dashboard' ) {
        $status_f = $_GET['status'] ?? '';
        $search_f = $_GET['search'] ?? '';
        $page_f   = max( 1, intval( $_GET['p'] ?? 1 ) );
        $qs = http_build_query( array_filter( array( 'status' => $status_f, 'search' => $search_f, 'page' => $page_f, 'per_page' => 50 ) ) );
        $bookings = wp_api( 'bookings?' . $qs );
    }
    if ( $action === 'booking_detail' && isset( $_GET['id'] ) ) {
        $booking = wp_api( 'bookings/' . intval( $_GET['id'] ) );
    }
    if ( $action === 'messages' )   $messages  = wp_api( 'messages' );
    if ( $action === 'customers' )  $customers = wp_api( 'customers' );
    if ( $action === 'analytics' )  $revenue   = wp_api( 'revenue?months=12' );
    if ( $action === 'dashboard' )  $revenue   = wp_api( 'revenue?months=6' );
}

$msg_param = htmlspecialchars( $_GET['msg'] ?? '' );
if ( $msg_param ) $msg = $msg_param;

// ─── Helpers ──────────────────────────────────────────────────────────────────
function status_badge( $s ) {
    $map = array(
        'nieuw'       => array( '#3b82f6', 'Nieuw' ),
        'bevestigd'   => array( '#6366f1', 'Bevestigd' ),
        'gepland'     => array( '#f59e0b', 'Gepland' ),
        'voltooid'    => array( '#10b981', 'Voltooid' ),
        'geannuleerd' => array( '#ef4444', 'Geannuleerd' ),
    );
    list( $c, $l ) = $map[ $s ] ?? array( '#64748b', ucfirst( $s ) );
    return "<span style='background:{$c}22;color:{$c};padding:2px 10px;border-radius:100px;font-size:11px;font-weight:700;border:1px solid {$c}44'>" . htmlspecialchars( $l ) . "</span>";
}
function fmt_eur( $val ) { return '€' . number_format( (float) $val, 2, ',', '.' ); }
function h( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>CleanMasterzz Portal<?php if ( $booking ) echo ' — #' . $booking['id']; ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#080b14;--bg2:#0d1220;--bg3:#111827;
  --card:rgba(255,255,255,.04);--card-h:rgba(255,255,255,.07);
  --border:rgba(255,255,255,.08);--border-h:rgba(255,255,255,.16);
  --text:#f1f5f9;--muted:#64748b;--dim:#94a3b8;
  --primary:#3b82f6;--grad:linear-gradient(135deg,#3b82f6,#8b5cf6);
  --r:12px;--r-sm:8px;--font:'Inter',-apple-system,sans-serif;
}
html{background:var(--bg);color:var(--text);font-family:var(--font);font-size:14px;line-height:1.5}
a{color:var(--primary);text-decoration:none}a:hover{color:var(--text)}
input,select,textarea{background:var(--card);border:1px solid var(--border);border-radius:var(--r-sm);color:var(--text);font-family:var(--font);font-size:13px;padding:8px 12px;width:100%;transition:.2s}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(59,130,246,.15)}
input[type=checkbox]{width:auto;accent-color:var(--primary)}
label{font-size:12px;font-weight:600;color:var(--dim);display:block;margin-bottom:5px}
select option{background:#1e293b}
.layout{display:flex;min-height:100vh}
.sidebar{width:230px;background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar-logo{padding:20px 18px;border-bottom:1px solid var(--border);font-size:15px;font-weight:800;display:flex;align-items:center;gap:10px}
.sidebar-logo span{background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sidebar-section{padding:10px 0}
.sidebar-label{padding:6px 18px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--muted)}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 18px;color:var(--muted);font-size:13px;font-weight:500;border-left:2px solid transparent;transition:.15s;text-decoration:none}
.nav-item:hover{color:var(--text);background:var(--card-h);color:var(--text)}
.nav-item.active{color:var(--text);background:var(--card);border-left-color:var(--primary)}
.nav-badge{margin-left:auto;background:rgba(239,68,68,.2);color:#fca5a5;border-radius:100px;padding:1px 7px;font-size:10px;font-weight:700}
.sidebar-footer{margin-top:auto;padding:14px 18px;border-top:1px solid var(--border)}
.main{flex:1;overflow:auto;padding:32px;max-width:100%}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:16px;flex-wrap:wrap}
.page-title{font-size:22px;font-weight:800}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px}
.stat{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:18px;display:flex;gap:14px;align-items:center;transition:.2s}
.stat:hover{border-color:var(--border-h);background:var(--card-h)}
.stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.stat-num{font-size:24px;font-weight:800;line-height:1}
.stat-label{font-size:11px;color:var(--muted);margin-top:3px}
.stat-sub{font-size:11px;color:var(--dim);margin-top:2px}
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;margin-bottom:20px}
.card-header{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;background:rgba(255,255,255,.02)}
.card-header h3{font-size:14px;font-weight:700}
table{width:100%;border-collapse:collapse}
th{padding:10px 16px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);border-bottom:1px solid var(--border);background:rgba(255,255,255,.02)}
td{padding:11px 16px;border-bottom:1px solid var(--border);color:var(--dim);font-size:13px}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--card-h)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--r-sm);font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:var(--font);transition:.2s;text-decoration:none}
.btn-primary{background:var(--grad);color:#fff}.btn-primary:hover{opacity:.9;color:#fff}
.btn-ghost{background:var(--card);border:1px solid var(--border);color:var(--dim)}.btn-ghost:hover{border-color:var(--border-h);color:var(--text);background:var(--card-h)}
.btn-danger{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#fca5a5}.btn-danger:hover{background:rgba(239,68,68,.25);color:#fca5a5}
.btn-sm{padding:5px 12px;font-size:12px}
.form-group{margin-bottom:14px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.alert{padding:10px 16px;border-radius:var(--r-sm);font-size:13px;margin-bottom:16px}
.alert-success{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);color:#6ee7b7}
.alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;padding:14px 20px;border-bottom:1px solid var(--border);background:rgba(255,255,255,.01)}
.filter-bar input,.filter-bar select{width:auto;flex:1;min-width:140px}
.empty{text-align:center;padding:48px;color:var(--muted);font-size:13px}
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-card{background:var(--bg2);border:1px solid var(--border);border-radius:20px;padding:40px;width:100%;max-width:380px;box-shadow:0 40px 80px rgba(0,0,0,.5)}
.login-logo{text-align:center;margin-bottom:28px}
.login-logo h1{font-size:22px;font-weight:800;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-top:10px}
.chart-wrap{padding:20px;height:280px;position:relative}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;padding:20px}
.detail-item label{font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px}
.detail-item .val{color:var(--text);font-size:14px;font-weight:500}
.msg-thread{padding:20px;display:flex;flex-direction:column;gap:12px}
.msg-bubble{padding:12px 16px;border-radius:var(--r);max-width:80%}
.msg-in{background:var(--card);border:1px solid var(--border);align-self:flex-start}
.msg-out{background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.2);align-self:flex-end}
.msg-meta{font-size:11px;color:var(--muted);margin-bottom:4px}
.msg-subject{font-weight:600;color:var(--text);font-size:13px;margin-bottom:4px}
.msg-body{color:var(--dim);font-size:13px;white-space:pre-wrap}
.pagination{display:flex;gap:8px;align-items:center;padding:12px 20px;border-top:1px solid var(--border)}
@media(max-width:900px){.stats{grid-template-columns:1fr 1fr}.sidebar{display:none}.form-row{grid-template-columns:1fr}}
</style>
</head>
<body>

<?php if ( $action === 'login' ): ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <span style="font-size:40px">🧹</span>
      <h1>CleanMasterzz Portal</h1>
      <p style="color:var(--muted);font-size:13px;margin-top:6px">Beheerdersdashboard</p>
    </div>
    <?php if($err): ?><div class="alert alert-error"><?=h($err)?></div><?php endif ?>
    <form method="post" action="/?action=login">
      <div class="form-group"><label>Gebruikersnaam</label><input name="username" type="text" required autofocus></div>
      <div class="form-group"><label>Wachtwoord</label><input name="password" type="password" required></div>
      <button class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Inloggen →</button>
    </form>
  </div>
</div>

<?php else: ?>
<div class="layout">

<aside class="sidebar">
  <div class="sidebar-logo">🧹 <span>CleanMasterzz</span></div>
  <div class="sidebar-section">
    <div class="sidebar-label">Overzicht</div>
    <a href="/" class="nav-item <?=($action==='dashboard')?'active':''?>">📊 Dashboard</a>
    <a href="/?action=analytics" class="nav-item <?=($action==='analytics')?'active':''?>">📈 Analytics</a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Beheer</div>
    <a href="/?action=bookings" class="nav-item <?=in_array($action,['bookings','booking_detail'])?'active':''?>">📋 Boekingen</a>
    <a href="/?action=customers" class="nav-item <?=($action==='customers')?'active':''?>">👥 Klanten</a>
    <a href="/?action=messages" class="nav-item <?=($action==='messages')?'active':''?>">
      💬 Berichten
      <?php if($stats&&($stats['unread_messages']??0)>0): ?><span class="nav-badge"><?=$stats['unread_messages']?></span><?php endif ?>
    </a>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Instellingen</div>
    <a href="<?=h(WP_SITE_URL)?>/wp-admin/" target="_blank" class="nav-item">⚙️ WordPress Admin ↗</a>
  </div>
  <div class="sidebar-footer">
    <a href="/?action=logout" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center">Uitloggen</a>
  </div>
</aside>

<main class="main">
  <?php if($msg): ?><div class="alert alert-success"><?=h($msg)?></div><?php endif ?>
  <?php if($err): ?><div class="alert alert-error"><?=h($err)?></div><?php endif ?>

  <?php if($action==='dashboard'): include __DIR__.'/partials/dashboard.php'; ?>
  <?php elseif($action==='bookings'): include __DIR__.'/partials/bookings.php'; ?>
  <?php elseif($action==='booking_detail'): include __DIR__.'/partials/booking-detail.php'; ?>
  <?php elseif($action==='messages'): include __DIR__.'/partials/messages.php'; ?>
  <?php elseif($action==='customers'): include __DIR__.'/partials/customers.php'; ?>
  <?php elseif($action==='analytics'): include __DIR__.'/partials/analytics.php'; ?>
  <?php endif ?>
</main>
</div>
<?php endif ?>

</body>
</html>
