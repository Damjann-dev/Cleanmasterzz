<?php
/**
 * Cleanmasterzz License Portal
 * Geserveerd op portal.damjanplugin.nl
 */

require_once dirname( __DIR__ ) . '/config.php';

session_name( SESSION_NAME );
session_start();

// ─── Auth ─────────────────────────────────────────────────────────────────────
function is_logged_in() { return ! empty( $_SESSION['ls_admin'] ); }
function require_login() {
    if ( ! is_logged_in() ) { header( 'Location: /?action=login' ); exit; }
}

// ─── DB ───────────────────────────────────────────────────────────────────────
function db() {
    static $pdo = null;
    if ( $pdo ) return $pdo;
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS,
        array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC )
    );
    return $pdo;
}

// ─── Tier defaults ────────────────────────────────────────────────────────────
function tier_defaults( $tier ) {
    $d = array(
        'free'   => array( 'analytics' => 0, 'pdf_invoices' => 0, 'boss_portal' => 0, 'custom_branding' => 0, 'multi_location' => 0, 'max_services' => 3 ),
        'pro'    => array( 'analytics' => 1, 'pdf_invoices' => 1, 'boss_portal' => 0, 'custom_branding' => 0, 'multi_location' => 1, 'max_services' => 10 ),
        'boss'   => array( 'analytics' => 1, 'pdf_invoices' => 1, 'boss_portal' => 1, 'custom_branding' => 0, 'multi_location' => 1, 'max_services' => 999 ),
        'agency' => array( 'analytics' => 1, 'pdf_invoices' => 1, 'boss_portal' => 1, 'custom_branding' => 1, 'multi_location' => 1, 'max_services' => 999 ),
    );
    return $d[ $tier ] ?? $d['free'];
}

// ─── Key generator ────────────────────────────────────────────────────────────
function gen_key() {
    $c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $k = 'CMCALC';
    for ( $g = 0; $g < 4; $g++ ) {
        $k .= '-';
        for ( $i = 0; $i < 4; $i++ ) $k .= $c[ random_int( 0, strlen($c)-1 ) ];
    }
    return $k;
}

// ─── Actions ──────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'dashboard';
$msg    = '';
$err    = '';

// Login
if ( $action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    if ( $user === ADMIN_USERNAME && password_verify( $pass, ADMIN_PASSWORD_HASH ) ) {
        $_SESSION['ls_admin'] = true;
        header( 'Location: /' ); exit;
    }
    $err = 'Onjuiste inloggegevens.';
}

// Logout
if ( $action === 'logout' ) {
    session_destroy();
    header( 'Location: /?action=login' ); exit;
}

if ( $action !== 'login' ) require_login();

// Licentie aanmaken
if ( $action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $tier    = in_array( $_POST['tier'] ?? '', array('free','pro','boss','agency') ) ? $_POST['tier'] : 'free';
    $email   = filter_var( $_POST['email'] ?? '', FILTER_VALIDATE_EMAIL );
    $name    = trim( $_POST['name'] ?? '' );
    $max_act = max(1, intval( $_POST['max_activations'] ?? 1 ));
    $max_svc = max(1, intval( $_POST['max_services'] ?? tier_defaults($tier)['max_services'] ));
    $expires = !empty($_POST['expires_at']) ? $_POST['expires_at'] . ' 23:59:59' : null;
    $notes   = trim( $_POST['notes'] ?? '' );

    // Custom features
    $defaults = tier_defaults($tier);
    $features = $defaults;
    foreach ( array('analytics','pdf_invoices','boss_portal','custom_branding','multi_location') as $f ) {
        $features[$f] = isset($_POST['feat_'.$f]) ? 1 : 0;
    }
    $features['max_services'] = $max_svc;

    if ( !$email ) { $err = 'Geldig e-mailadres verplicht.'; }
    else {
        try {
            $key = gen_key();
            db()->prepare('INSERT INTO licenses (license_key,tier,email,name,max_activations,max_services,features,expires_at,notes) VALUES (?,?,?,?,?,?,?,?,?)')
               ->execute(array($key,$tier,$email,$name,$max_act,$max_svc,json_encode($features),$expires,$notes));
            header('Location: /?action=view&id=' . db()->lastInsertId() . '&msg=created'); exit;
        } catch(Exception $e) { $err = 'Fout: ' . $e->getMessage(); }
    }
}

// Licentie opslaan
if ( $action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $id      = intval( $_POST['id'] ?? 0 );
    $tier    = in_array($_POST['tier']??'',array('free','pro','boss','agency')) ? $_POST['tier'] : 'free';
    $status  = in_array($_POST['status']??'',array('active','suspended','expired')) ? $_POST['status'] : 'active';
    $max_act = max(1, intval($_POST['max_activations']??1));
    $max_svc = max(1, intval($_POST['max_services']??3));
    $expires = !empty($_POST['expires_at']) ? $_POST['expires_at'].' 23:59:59' : null;
    $notes   = trim($_POST['notes']??'');
    $name    = trim($_POST['name']??'');
    $email   = trim($_POST['email']??'');

    $features = tier_defaults($tier);
    foreach ( array('analytics','pdf_invoices','boss_portal','custom_branding','multi_location') as $f ) {
        $features[$f] = isset($_POST['feat_'.$f]) ? 1 : 0;
    }
    $features['max_services'] = $max_svc;

    db()->prepare('UPDATE licenses SET tier=?,status=?,email=?,name=?,max_activations=?,max_services=?,features=?,expires_at=?,notes=? WHERE id=?')
       ->execute(array($tier,$status,$email,$name,$max_act,$max_svc,json_encode($features),$expires,$notes,$id));
    header('Location: /?action=view&id='.$id.'&msg=saved'); exit;
}

// Licentie verwijderen
if ( $action === 'delete' && isset($_GET['id']) ) {
    db()->prepare('DELETE FROM licenses WHERE id=?')->execute(array(intval($_GET['id'])));
    header('Location: /?msg=deleted'); exit;
}

// Activatie verwijderen
if ( $action === 'del_activation' && isset($_GET['id']) ) {
    $lid = intval($_GET['license_id']??0);
    db()->prepare('DELETE FROM license_activations WHERE id=?')->execute(array(intval($_GET['id'])));
    header('Location: /?action=view&id='.$lid); exit;
}

// ─── Data ophalen ─────────────────────────────────────────────────────────────
$stats = array('total'=>0,'active'=>0,'suspended'=>0,'expired'=>0);
$view_license = null;
$activations  = array();
$licenses     = array();

if ( is_logged_in() ) {
    $row = db()->query('SELECT COUNT(*) as t, SUM(status="active") as a, SUM(status="suspended") as s, SUM(status="expired") as e FROM licenses')->fetch();
    $stats = array('total'=>$row['t'],'active'=>$row['a'],'suspended'=>$row['s'],'expired'=>$row['e']);

    if ( in_array($action, array('dashboard','search')) ) {
        $search = trim($_GET['q']??'');
        $tier_f = $_GET['tier']??'';
        $status_f = $_GET['status']??'';
        $sql = 'SELECT l.*, (SELECT COUNT(*) FROM license_activations WHERE license_id=l.id) as act_count FROM licenses l WHERE 1';
        $params = array();
        if ($search) { $sql .= ' AND (l.email LIKE ? OR l.license_key LIKE ? OR l.name LIKE ?)'; $params = array_merge($params,array("%$search%","%$search%","%$search%")); }
        if ($tier_f) { $sql .= ' AND l.tier=?'; $params[] = $tier_f; }
        if ($status_f) { $sql .= ' AND l.status=?'; $params[] = $status_f; }
        $sql .= ' ORDER BY l.created_at DESC LIMIT 100';
        $stmt = db()->prepare($sql); $stmt->execute($params);
        $licenses = $stmt->fetchAll();
    }

    if ( $action === 'view' && isset($_GET['id']) ) {
        $view_license = db()->prepare('SELECT * FROM licenses WHERE id=?')->execute(array(intval($_GET['id']))) ? db()->prepare('SELECT * FROM licenses WHERE id=?') : null;
        $stmt = db()->prepare('SELECT * FROM licenses WHERE id=?'); $stmt->execute(array(intval($_GET['id']))); $view_license = $stmt->fetch();
        if ($view_license) {
            $stmt2 = db()->prepare('SELECT * FROM license_activations WHERE license_id=? ORDER BY last_seen DESC');
            $stmt2->execute(array($view_license['id'])); $activations = $stmt2->fetchAll();
            $msg_map = array('saved'=>'Opgeslagen.','created'=>'Licentie aangemaakt.');
            $msg = $msg_map[$_GET['msg']??''] ?? '';
        }
    }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function tier_badge($t) {
    $c = array('free'=>'#64748b','pro'=>'#6366f1','boss'=>'#f59e0b','agency'=>'#10b981');
    $color = $c[$t]??'#64748b';
    return "<span style='background:".htmlspecialchars($color)."22;color:".htmlspecialchars($color).";padding:2px 10px;border-radius:100px;font-size:11px;font-weight:700;border:1px solid ".htmlspecialchars($color)."44'>".strtoupper(htmlspecialchars($t))."</span>";
}
function status_badge($s) {
    $map = array('active'=>array('#10b981','Actief'),'suspended'=>array('#f59e0b','Gepauzeerd'),'expired'=>array('#ef4444','Verlopen'));
    list($c,$l) = $map[$s]??array('#64748b',$s);
    return "<span style='background:{$c}22;color:{$c};padding:2px 10px;border-radius:100px;font-size:11px;font-weight:700;border:1px solid {$c}44'>$l</span>";
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Servicezz Portal<?php if($view_license) echo ' — '.htmlspecialchars($view_license['name']?:$view_license['email']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
a{color:var(--primary);text-decoration:none}
a:hover{color:var(--text)}
input,select,textarea{background:var(--card);border:1px solid var(--border);border-radius:var(--r-sm);color:var(--text);font-family:var(--font);font-size:13px;padding:8px 12px;width:100%;transition:.2s}
input:focus,select:focus,textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(59,130,246,.15)}
input[type=checkbox]{width:auto}
label{font-size:12px;font-weight:600;color:var(--dim);display:block;margin-bottom:5px}
select option{background:#1e293b}

/* Layout */
.layout{display:flex;min-height:100vh}
.sidebar{width:220px;background:var(--bg2);border-right:1px solid var(--border);display:flex;flex-direction:column;flex-shrink:0}
.sidebar-logo{padding:20px 18px;border-bottom:1px solid var(--border);font-size:16px;font-weight:800;display:flex;align-items:center;gap:10px}
.sidebar-logo span{background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.sidebar-nav{padding:12px 0;flex:1}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 18px;color:var(--muted);font-size:13px;font-weight:500;border-left:2px solid transparent;transition:.2s;text-decoration:none}
.nav-item:hover{color:var(--text);background:var(--card-h)}
.nav-item.active{color:var(--text);background:var(--card);border-left-color:var(--primary)}
.sidebar-footer{padding:14px 18px;border-top:1px solid var(--border)}
.main{flex:1;overflow:auto;padding:32px}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;gap:16px;flex-wrap:wrap}
.page-title{font-size:22px;font-weight:800}

/* Stat cards */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px}
.stat{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:18px;display:flex;gap:14px;align-items:center}
.stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.stat-num{font-size:22px;font-weight:800;line-height:1}
.stat-label{font-size:11px;color:var(--muted);margin-top:3px}

/* Table */
.card{background:var(--card);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;margin-bottom:20px}
.card-header{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;background:rgba(255,255,255,.02)}
.card-header h3{font-size:14px;font-weight:700}
table{width:100%;border-collapse:collapse}
th{padding:10px 16px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);border-bottom:1px solid var(--border);background:rgba(255,255,255,.02)}
td{padding:12px 16px;border-bottom:1px solid var(--border);color:var(--dim);font-size:13px}
tr:last-child td{border-bottom:none}
tr:hover td{background:var(--card-h);color:var(--text)}

/* Buttons */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:var(--r-sm);font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:var(--font);transition:.2s;text-decoration:none}
.btn-primary{background:var(--grad);color:#fff}
.btn-primary:hover{opacity:.9;color:#fff}
.btn-ghost{background:var(--card);border:1px solid var(--border);color:var(--dim)}
.btn-ghost:hover{border-color:var(--border-h);color:var(--text);background:var(--card-h)}
.btn-danger{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.btn-danger:hover{background:rgba(239,68,68,.25)}
.btn-sm{padding:5px 12px;font-size:12px}

/* Forms */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.form-group{margin-bottom:16px}
.form-section{margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border)}
.form-section:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.form-section h4{font-size:13px;font-weight:700;color:var(--dim);text-transform:uppercase;letter-spacing:.05em;margin-bottom:16px}
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.feature-toggle{background:var(--bg3);border:1px solid var(--border);border-radius:var(--r-sm);padding:12px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:.2s}
.feature-toggle:hover{border-color:var(--border-h)}
.feature-toggle input{width:16px;height:16px;accent-color:var(--primary);flex-shrink:0}
.feature-toggle .ft-label{font-size:12px;font-weight:600;color:var(--dim)}
.feature-toggle .ft-sub{font-size:11px;color:var(--muted);margin-top:1px}

/* Search bar */
.search-bar{display:flex;gap:10px;flex-wrap:wrap}
.search-bar input,.search-bar select{width:auto;flex:1;min-width:160px}

/* Alerts */
.alert{padding:10px 16px;border-radius:var(--r-sm);font-size:13px;margin-bottom:16px}
.alert-success{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);color:#6ee7b7}
.alert-error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);color:#fca5a5}

/* Login page */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg)}
.login-card{background:var(--bg2);border:1px solid var(--border);border-radius:20px;padding:40px;width:100%;max-width:400px;box-shadow:0 40px 80px rgba(0,0,0,.5)}
.login-logo{text-align:center;margin-bottom:28px}
.login-logo .logo-icon{font-size:40px;display:block;margin-bottom:8px}
.login-logo h1{font-size:22px;font-weight:800;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent}

/* Misc */
.key-display{font-family:monospace;font-size:14px;font-weight:700;color:var(--primary);background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2);padding:10px 16px;border-radius:var(--r-sm);letter-spacing:.05em}
.empty{text-align:center;padding:48px;color:var(--muted)}
.services-slider{margin-top:8px}
.services-slider input[type=range]{width:100%;accent-color:var(--primary)}
.services-val{font-size:20px;font-weight:800;color:var(--primary);margin-left:8px}
@media(max-width:768px){.stats{grid-template-columns:1fr 1fr}.form-grid{grid-template-columns:1fr}.feature-grid{grid-template-columns:1fr 1fr}.sidebar{display:none}.layout{flex-direction:column}}
</style>
</head>
<body>

<?php if ( $action === 'login' ): ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <span class="logo-icon">⚡</span>
      <h1>Servicezz Portal</h1>
      <p style="color:var(--muted);font-size:13px;margin-top:4px">Licentie beheer</p>
    </div>
    <?php if($err): ?><div class="alert alert-error"><?=htmlspecialchars($err)?></div><?php endif ?>
    <form method="post" action="/?action=login">
      <div class="form-group"><label>Gebruikersnaam</label><input name="username" type="text" required autofocus></div>
      <div class="form-group"><label>Wachtwoord</label><input name="password" type="password" required></div>
      <button class="btn btn-primary" style="width:100%;justify-content:center;padding:12px">Inloggen →</button>
    </form>
  </div>
</div>

<?php else: ?>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">⚡ <span>Portal</span></div>
    <nav class="sidebar-nav">
      <a href="/" class="nav-item <?=in_array($action,array('dashboard','search'))?'active':''?>">📋 Licenties</a>
      <a href="/?action=create_form" class="nav-item <?=$action==='create_form'?'active':''?>">➕ Nieuwe licentie</a>
      <a href="/?action=logs" class="nav-item <?=$action==='logs'?'active':''?>">📜 API Logs</a>
    </nav>
    <div class="sidebar-footer">
      <a href="/?action=logout" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center">Uitloggen</a>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">

    <?php if($msg): ?><div class="alert alert-success"><?=htmlspecialchars($msg)?></div><?php endif ?>
    <?php if($err): ?><div class="alert alert-error"><?=htmlspecialchars($err)?></div><?php endif ?>

    <?php /* ──── DASHBOARD ──────────────────────────────────────────── */ ?>
    <?php if ( in_array($action, array('dashboard','search')) ): ?>

    <div class="topbar">
      <h1 class="page-title">Licenties</h1>
      <a href="/?action=create_form" class="btn btn-primary">➕ Nieuwe licentie</a>
    </div>

    <div class="stats">
      <div class="stat"><div class="stat-icon" style="background:rgba(59,130,246,.15)">📋</div><div><div class="stat-num"><?=$stats['total']?></div><div class="stat-label">Totaal</div></div></div>
      <div class="stat"><div class="stat-icon" style="background:rgba(16,185,129,.15)">✅</div><div><div class="stat-num"><?=$stats['active']?></div><div class="stat-label">Actief</div></div></div>
      <div class="stat"><div class="stat-icon" style="background:rgba(245,158,11,.15)">⏸</div><div><div class="stat-num"><?=$stats['suspended']?></div><div class="stat-label">Gepauzeerd</div></div></div>
      <div class="stat"><div class="stat-icon" style="background:rgba(239,68,68,.15)">❌</div><div><div class="stat-num"><?=$stats['expired']?></div><div class="stat-label">Verlopen</div></div></div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>Zoeken & filteren</h3>
      </div>
      <div style="padding:16px">
        <form method="get" action="/" class="search-bar">
          <input name="q" placeholder="Zoek op e-mail, naam of sleutel..." value="<?=htmlspecialchars($_GET['q']??'')?>">
          <select name="tier">
            <option value="">Alle tiers</option>
            <?php foreach(array('free','pro','boss','agency') as $t): ?>
            <option value="<?=$t?>" <?=($_GET['tier']??'')===$t?'selected':''?>><?=ucfirst($t)?></option>
            <?php endforeach ?>
          </select>
          <select name="status">
            <option value="">Alle statussen</option>
            <option value="active" <?=($_GET['status']??'')==='active'?'selected':''?>>Actief</option>
            <option value="suspended" <?=($_GET['status']??'')==='suspended'?'selected':''?>>Gepauzeerd</option>
            <option value="expired" <?=($_GET['status']??'')==='expired'?'selected':''?>>Verlopen</option>
          </select>
          <input type="hidden" name="action" value="search">
          <button class="btn btn-primary" type="submit">Zoeken</button>
          <?php if(!empty($_GET['q'])||!empty($_GET['tier'])||!empty($_GET['status'])): ?><a href="/" class="btn btn-ghost">Wissen</a><?php endif ?>
        </form>
      </div>
      <?php if(empty($licenses)): ?>
      <div class="empty">Geen licenties gevonden.</div>
      <?php else: ?>
      <table>
        <thead><tr><th>Sleutel</th><th>Klant</th><th>Tier</th><th>Status</th><th>Sites</th><th>Diensten</th><th>Aangemaakt</th><th></th></tr></thead>
        <tbody>
        <?php foreach($licenses as $l): ?>
        <tr>
          <td><a href="/?action=view&id=<?=$l['id']?>" style="font-family:monospace;font-size:12px;color:var(--primary)"><?=htmlspecialchars($l['license_key'])?></a></td>
          <td>
            <div style="font-weight:600;color:var(--text)"><?=htmlspecialchars($l['name']?:'-')?></div>
            <div style="font-size:12px;color:var(--muted)"><?=htmlspecialchars($l['email'])?></div>
          </td>
          <td><?=tier_badge($l['tier'])?></td>
          <td><?=status_badge($l['status'])?></td>
          <td style="color:var(--text)"><?=$l['act_count']?>/<?=$l['max_activations']?></td>
          <td style="color:var(--text)"><?=$l['max_services']?></td>
          <td style="color:var(--muted)"><?=date('d M Y',strtotime($l['created_at']))?></td>
          <td><a href="/?action=view&id=<?=$l['id']?>" class="btn btn-ghost btn-sm">Bewerken</a></td>
        </tr>
        <?php endforeach ?>
        </tbody>
      </table>
      <?php endif ?>
    </div>

    <?php /* ──── NIEUWE LICENTIE ──────────────────────────────────── */ ?>
    <?php elseif($action==='create_form'): ?>
    <div class="topbar"><h1 class="page-title">Nieuwe licentie</h1><a href="/" class="btn btn-ghost">← Terug</a></div>
    <?php include __DIR__.'/partials/license-form.php'; ?>

    <?php /* ──── LICENTIE BEKIJKEN / BEWERKEN ───────────────────── */ ?>
    <?php elseif($action==='view' && $view_license): ?>

    <div class="topbar">
      <div>
        <h1 class="page-title"><?=htmlspecialchars($view_license['name']?:$view_license['email'])?></h1>
        <div style="color:var(--muted);font-size:13px;margin-top:4px"><?=htmlspecialchars($view_license['email'])?></div>
      </div>
      <div style="display:flex;gap:10px">
        <a href="/" class="btn btn-ghost">← Terug</a>
        <a href="/?action=delete&id=<?=$view_license['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Licentie verwijderen?')">🗑 Verwijderen</a>
      </div>
    </div>

    <div class="key-display" style="margin-bottom:20px"><?=htmlspecialchars($view_license['license_key'])?></div>

    <?php include __DIR__.'/partials/license-form.php'; ?>

    <!-- Activaties -->
    <div class="card" style="margin-top:24px">
      <div class="card-header"><h3>Actieve installaties (<?=count($activations)?>)</h3></div>
      <?php if(empty($activations)): ?>
      <div class="empty">Nog geen activaties.</div>
      <?php else: ?>
      <table>
        <thead><tr><th>Domein</th><th>Plugin versie</th><th>Geactiveerd</th><th>Laatste check</th><th>IP</th><th></th></tr></thead>
        <tbody>
        <?php foreach($activations as $a): ?>
        <tr>
          <td style="color:var(--text);font-weight:600"><?=htmlspecialchars($a['domain'])?></td>
          <td><?=htmlspecialchars($a['plugin_ver']?:'-')?></td>
          <td style="color:var(--muted)"><?=date('d M Y',strtotime($a['activated_at']))?></td>
          <td style="color:var(--muted)"><?=date('d M Y H:i',strtotime($a['last_seen']))?></td>
          <td style="color:var(--muted);font-size:12px"><?=htmlspecialchars($a['ip']?:'-')?></td>
          <td><a href="/?action=del_activation&id=<?=$a['id']?>&license_id=<?=$view_license['id']?>" class="btn btn-danger btn-sm" onclick="return confirm('Activatie verwijderen?')">✕</a></td>
        </tr>
        <?php endforeach ?>
        </tbody>
      </table>
      <?php endif ?>
    </div>

    <?php /* ──── API LOGS ───────────────────────────────────────── */ ?>
    <?php elseif($action==='logs'): ?>
    <div class="topbar"><h1 class="page-title">API Logs</h1></div>
    <div class="card">
      <?php
      $logs = db()->query('SELECT * FROM api_logs ORDER BY created_at DESC LIMIT 200')->fetchAll();
      if(empty($logs)): ?><div class="empty">Geen logs.</div><?php else: ?>
      <table>
        <thead><tr><th>Tijd</th><th>Endpoint</th><th>Sleutel</th><th>Domein</th><th>Resultaat</th><th>Melding</th></tr></thead>
        <tbody>
        <?php foreach($logs as $log): ?>
        <tr>
          <td style="color:var(--muted);font-size:12px;white-space:nowrap"><?=date('d M H:i',strtotime($log['created_at']))?></td>
          <td><code style="font-size:12px"><?=htmlspecialchars($log['endpoint'])?></code></td>
          <td style="font-family:monospace;font-size:11px;color:var(--muted)"><?=htmlspecialchars(substr($log['license_key']??'-',0,16))?></td>
          <td style="font-size:12px"><?=htmlspecialchars($log['domain']??'-')?></td>
          <td><?php
            $rc = array('success'=>'#10b981','fail'=>'#f59e0b','error'=>'#ef4444');
            $c = $rc[$log['result']]??'#64748b';
            echo "<span style='color:{$c};font-weight:700;font-size:11px'>".strtoupper(htmlspecialchars($log['result']))."</span>";
          ?></td>
          <td style="color:var(--muted);font-size:12px"><?=htmlspecialchars($log['message']??'')?></td>
        </tr>
        <?php endforeach ?>
        </tbody>
      </table>
      <?php endif ?>
    </div>
    <?php endif ?>

  </main>
</div>
<?php endif ?>

<script>
// Max services slider live update
document.addEventListener('DOMContentLoaded', function() {
    var slider = document.getElementById('max_services_range');
    var val    = document.getElementById('max_services_val');
    var hidden = document.getElementById('max_services');
    if (!slider) return;
    function update() {
        var v = slider.value;
        val.textContent  = v >= 999 ? '∞' : v;
        hidden.value     = v;
    }
    slider.addEventListener('input', update);
    update();
});
</script>
</body>
</html>
