<?php
/**
 * Cleanmasterzz License Server — Admin Panel
 *
 * Beheer licenties, aanmaken van keys, bekijken van activaties.
 */

require_once dirname( __DIR__ ) . '/config.php';

session_name( SESSION_NAME );
session_start();

// ─── Authenticatie ────────────────────────────────────────────────────────
function is_logged_in() {
    return ! empty( $_SESSION['ls_admin'] ) && $_SESSION['ls_admin'] === true;
}

function require_login() {
    if ( ! is_logged_in() ) {
        header( 'Location: ' . ADMIN_BASE . '/?action=login' );
        exit;
    }
}

// ─── DB ────────────────────────────────────────────────────────────────────
function get_pdo() {
    static $pdo = null;
    if ( $pdo ) return $pdo;
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        )
    );
    return $pdo;
}

// ─── Key generator ─────────────────────────────────────────────────────────
function generate_license_key() {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Geen 0,O,1,I voor duidelijkheid
    $key   = 'CMCALC';
    for ( $g = 0; $g < 4; $g++ ) {
        $key .= '-';
        for ( $c = 0; $c < 4; $c++ ) {
            $key .= $chars[ random_int( 0, strlen( $chars ) - 1 ) ];
        }
    }
    return $key;
}

// ─── CSRF token ────────────────────────────────────────────────────────────
if ( empty( $_SESSION['csrf_token'] ) ) {
    $_SESSION['csrf_token'] = bin2hex( random_bytes( 32 ) );
}
function csrf_token() { return $_SESSION['csrf_token']; }
function verify_csrf() {
    $token = $_POST['csrf_token'] ?? '';
    if ( ! hash_equals( $_SESSION['csrf_token'], $token ) ) {
        die( 'CSRF fout. Probeer opnieuw.' );
    }
}

// ─── Routing ───────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? 'dashboard';

// Login
if ( $action === 'login' ) {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
        verify_csrf();
        $user = trim( $_POST['username'] ?? '' );
        $pass = $_POST['password'] ?? '';
        if ( $user === ADMIN_USERNAME && password_verify( $pass, ADMIN_PASSWORD_HASH ) ) {
            $_SESSION['ls_admin']      = true;
            $_SESSION['ls_admin_user'] = $user;
            session_regenerate_id( true );
            header( 'Location: ' . ADMIN_BASE . '/' );
            exit;
        }
        $login_error = 'Onjuiste gebruikersnaam of wachtwoord.';
    }
    render_login( $login_error ?? '' );
    exit;
}

if ( $action === 'logout' ) {
    session_destroy();
    header( 'Location: ' . ADMIN_BASE . '/?action=login' );
    exit;
}

require_login();

// Aanmaken licentie
if ( $action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    verify_csrf();
    $pdo = get_pdo();

    $tier         = in_array( $_POST['tier'], array( 'pro', 'boss', 'agency' ) ) ? $_POST['tier'] : 'pro';
    $email        = filter_var( trim( $_POST['email'] ), FILTER_VALIDATE_EMAIL );
    $billing_type = in_array( $_POST['billing_type'], array( 'monthly', 'yearly', 'lifetime' ) ) ? $_POST['billing_type'] : 'yearly';
    $max_act      = max( 1, intval( $_POST['max_activations'] ?? 1 ) );
    $notes        = substr( trim( $_POST['notes'] ?? '' ), 0, 500 );

    // Vervaldatum
    $expires_at = null;
    if ( $billing_type === 'monthly' ) {
        $expires_at = date( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
    } elseif ( $billing_type === 'yearly' ) {
        $expires_at = date( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
    }

    // Genereer unieke key
    do {
        $key = generate_license_key();
        $exists = $pdo->prepare( 'SELECT id FROM licenses WHERE license_key = :key' );
        $exists->execute( array( ':key' => $key ) );
    } while ( $exists->fetch() );

    if ( ! $email ) {
        $flash_error = 'Ongeldig e-mailadres.';
    } else {
        $stmt = $pdo->prepare( '
            INSERT INTO licenses (license_key, tier, email, billing_type, max_activations, expires_at, notes)
            VALUES (:key, :tier, :email, :billing, :max, :expires, :notes)
        ' );
        $stmt->execute( array(
            ':key'     => $key,
            ':tier'    => $tier,
            ':email'   => $email,
            ':billing' => $billing_type,
            ':max'     => $max_act,
            ':expires' => $expires_at,
            ':notes'   => $notes,
        ) );
        $flash_success = 'Licentie aangemaakt: <strong>' . htmlspecialchars( $key ) . '</strong>';
    }
    header( 'Location: ' . ADMIN_BASE . '/?flash=' . urlencode( $flash_success ?? '' ) . '&err=' . urlencode( $flash_error ?? '' ) );
    exit;
}

// Status wijzigen
if ( $action === 'toggle_status' && isset( $_GET['id'] ) ) {
    verify_csrf_get();
    $pdo = get_pdo();
    $id  = intval( $_GET['id'] );
    $new = $_GET['new_status'] ?? 'active';
    if ( in_array( $new, array( 'active', 'suspended', 'expired' ) ) ) {
        $pdo->prepare( 'UPDATE licenses SET status = :s WHERE id = :id' )
            ->execute( array( ':s' => $new, ':id' => $id ) );
    }
    header( 'Location: ' . ADMIN_BASE . '/' );
    exit;
}

// Verwijderen
if ( $action === 'delete' && isset( $_GET['id'] ) ) {
    verify_csrf_get();
    $pdo = get_pdo();
    $pdo->prepare( 'DELETE FROM licenses WHERE id = :id' )
        ->execute( array( ':id' => intval( $_GET['id'] ) ) );
    header( 'Location: ' . ADMIN_BASE . '/' );
    exit;
}

// Activaties detail
if ( $action === 'activations' && isset( $_GET['id'] ) ) {
    $pdo  = get_pdo();
    $id   = intval( $_GET['id'] );
    $stmt = $pdo->prepare( 'SELECT * FROM licenses WHERE id = :id LIMIT 1' );
    $stmt->execute( array( ':id' => $id ) );
    $lic  = $stmt->fetch();
    $stmt = $pdo->prepare( 'SELECT * FROM license_activations WHERE license_id = :id ORDER BY last_seen DESC' );
    $stmt->execute( array( ':id' => $id ) );
    $acts = $stmt->fetchAll();
    render_activations( $lic, $acts );
    exit;
}

// CSRF get helper
function verify_csrf_get() {
    $token = $_GET['csrf'] ?? '';
    if ( ! hash_equals( $_SESSION['csrf_token'], $token ) ) die( 'CSRF fout.' );
}

// ─── Dashboard data ─────────────────────────────────────────────────────────
$pdo = get_pdo();

$filter_tier   = $_GET['tier'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search        = trim( $_GET['q'] ?? '' );

$where  = array( '1=1' );
$params = array();
if ( $filter_tier )   { $where[] = 'tier = :tier';     $params[':tier']   = $filter_tier; }
if ( $filter_status ) { $where[] = 'status = :status'; $params[':status'] = $filter_status; }
if ( $search ) {
    $where[]         = '(license_key LIKE :q OR email LIKE :q)';
    $params[':q']    = '%' . $search . '%';
}

$sql = 'SELECT * FROM licenses WHERE ' . implode( ' AND ', $where ) . ' ORDER BY created_at DESC';
$stmt = $pdo->prepare( $sql );
$stmt->execute( $params );
$licenses = $stmt->fetchAll();

// Stats
$stats_stmt = $pdo->query( "
    SELECT
        COUNT(*) as total,
        SUM(status='active') as active,
        SUM(status='suspended') as suspended,
        SUM(status='expired') as expired,
        SUM(tier='pro') as pro_count,
        SUM(tier='boss') as boss_count,
        SUM(tier='agency') as agency_count
    FROM licenses
" );
$stats = $stats_stmt->fetch();

render_dashboard( $licenses, $stats );

// ─── RENDER FUNCTIES ────────────────────────────────────────────────────────

function render_login( $error ) {
    $csrf = csrf_token();
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>License Server — Login</title>
        <style>
            *, *::before, *::after { box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #1B2A4A 0%, #2d4a8a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
            .card { background: #fff; border-radius: 20px; padding: 48px 40px; width: 380px; box-shadow: 0 24px 64px rgba(0,0,0,0.3); }
            .logo { font-size: 22px; font-weight: 800; color: #1B2A4A; margin-bottom: 8px; }
            .sub { color: #94a3b8; font-size: 13px; margin-bottom: 32px; }
            label { display: block; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
            input { width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; margin-bottom: 16px; transition: border-color 0.2s; }
            input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
            button { width: 100%; padding: 13px; background: #1B2A4A; color: #fff; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; margin-top: 8px; }
            button:hover { background: #2d4a8a; }
            .error { background: #fef2f2; color: #dc2626; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="logo">Cleanmasterzz</div>
            <div class="sub">License Server Admin</div>
            <?php if ( $error ) : ?><div class="error"><?php echo htmlspecialchars( $error ); ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <label>Gebruikersnaam</label>
                <input type="text" name="username" autocomplete="username" required>
                <label>Wachtwoord</label>
                <input type="password" name="password" autocomplete="current-password" required>
                <button type="submit">Inloggen</button>
            </form>
        </div>
    </body>
    </html>
    <?php
}

function render_dashboard( $licenses, $stats ) {
    $csrf          = csrf_token();
    $filter_tier   = $_GET['tier'] ?? '';
    $filter_status = $_GET['status'] ?? '';
    $search        = trim( $_GET['q'] ?? '' );
    $flash_ok  = htmlspecialchars( urldecode( $_GET['flash'] ?? '' ) );
    $flash_err = htmlspecialchars( urldecode( $_GET['err'] ?? '' ) );
    $tier_badges = array(
        'pro'    => '<span style="background:linear-gradient(135deg,#3b82f6,#6366f1);color:#fff;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;">Pro</span>',
        'boss'   => '<span style="background:linear-gradient(135deg,#f59e0b,#ec4899);color:#fff;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;">Boss</span>',
        'agency' => '<span style="background:linear-gradient(135deg,#10b981,#06b6d4);color:#fff;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;">Agency</span>',
    );
    $status_badges = array(
        'active'    => '<span style="background:#dcfce7;color:#16a34a;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;">Actief</span>',
        'suspended' => '<span style="background:#fef9c3;color:#ca8a04;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;">Opgeschort</span>',
        'expired'   => '<span style="background:#fee2e2;color:#dc2626;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700;">Verlopen</span>',
    );
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>License Server — Dashboard</title>
        <style>
            *, *::before, *::after { box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; color: #1e293b; }
            .nav { background: #1B2A4A; color: #fff; padding: 0 32px; display: flex; align-items: center; justify-content: space-between; height: 60px; }
            .nav-brand { font-size: 18px; font-weight: 800; }
            .nav-right a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; }
            .nav-right a:hover { color: #fff; }
            .main { max-width: 1200px; margin: 32px auto; padding: 0 24px; }
            .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
            .stat-card { background: #fff; border-radius: 14px; padding: 20px 24px; border: 1px solid #e2e8f0; }
            .stat-num { font-size: 28px; font-weight: 800; color: #1B2A4A; }
            .stat-label { font-size: 13px; color: #64748b; margin-top: 4px; }
            .card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; }
            .card-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
            .card-header h2 { margin: 0; font-size: 17px; }
            .btn { padding: 9px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; display: inline-block; }
            .btn-primary { background: #1B2A4A; color: #fff; }
            .btn-primary:hover { background: #2d4a8a; }
            .btn-sm { padding: 5px 12px; font-size: 12px; }
            .btn-danger { background: #fee2e2; color: #dc2626; }
            .btn-warning { background: #fef9c3; color: #ca8a04; }
            .btn-success { background: #dcfce7; color: #16a34a; }
            table { width: 100%; border-collapse: collapse; }
            th { padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; }
            td { padding: 14px 16px; font-size: 13px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
            tr:hover td { background: #f8fafc; }
            .mono { font-family: monospace; letter-spacing: 1px; font-size: 12px; }
            .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 24px; }
            .form-group { display: flex; flex-direction: column; gap: 6px; }
            .form-group label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
            .form-group input, .form-group select, .form-group textarea { padding: 9px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; }
            .flash { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; }
            .flash-ok  { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
            .flash-err { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
            .filters { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
            .filters input, .filters select { padding: 8px 12px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 13px; }
        </style>
    </head>
    <body>
        <div class="nav">
            <div class="nav-brand">Cleanmasterzz — License Server</div>
            <div class="nav-right">
                <a href="<?php echo ADMIN_BASE; ?>/?action=logout">Uitloggen</a>
            </div>
        </div>

        <div class="main">
            <?php if ( $flash_ok )  : ?><div class="flash flash-ok"><?php echo $flash_ok; ?></div><?php endif; ?>
            <?php if ( $flash_err ) : ?><div class="flash flash-err"><?php echo $flash_err; ?></div><?php endif; ?>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-num"><?php echo intval($stats['total']); ?></div>
                    <div class="stat-label">Totaal licenties</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num" style="color:#16a34a;"><?php echo intval($stats['active']); ?></div>
                    <div class="stat-label">Actief</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num" style="color:#f59e0b;"><?php echo intval($stats['boss_count']); ?></div>
                    <div class="stat-label">Boss licenties</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num" style="color:#6366f1;"><?php echo intval($stats['agency_count']); ?></div>
                    <div class="stat-label">Agency licenties</div>
                </div>
            </div>

            <!-- Nieuwe licentie aanmaken -->
            <div class="card" style="margin-bottom:24px;">
                <div class="card-header">
                    <h2>Nieuwe licentie aanmaken</h2>
                </div>
                <form method="post" action="<?php echo ADMIN_BASE; ?>/?action=create">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tier</label>
                            <select name="tier">
                                <option value="pro">Professional</option>
                                <option value="boss">Boss</option>
                                <option value="agency">Agency</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Klant e-mailadres</label>
                            <input type="email" name="email" placeholder="klant@bedrijf.nl" required>
                        </div>
                        <div class="form-group">
                            <label>Facturatie type</label>
                            <select name="billing_type">
                                <option value="yearly">Jaarlijks (1 jaar geldig)</option>
                                <option value="monthly">Maandelijks (1 maand geldig)</option>
                                <option value="lifetime">Lifetime</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Max activaties (domeinen)</label>
                            <input type="number" name="max_activations" value="1" min="1" max="50">
                        </div>
                        <div class="form-group" style="grid-column:1/-1;">
                            <label>Notities (intern)</label>
                            <textarea name="notes" rows="2" placeholder="Factuur #123, betaald via Stripe..."></textarea>
                        </div>
                    </div>
                    <div style="padding: 0 24px 20px;">
                        <button type="submit" class="btn btn-primary">+ Licentie genereren &amp; opslaan</button>
                    </div>
                </form>
            </div>

            <!-- Licentielijst -->
            <div class="card">
                <div class="card-header">
                    <h2>Alle licenties</h2>
                    <form method="get" action="<?php echo ADMIN_BASE; ?>/" class="filters">
                        <input type="text" name="q" placeholder="Zoek key of email..." value="<?php echo htmlspecialchars( $_GET['q'] ?? '' ); ?>" style="width:200px;">
                        <select name="tier">
                            <option value="">Alle tiers</option>
                            <option value="pro" <?php echo $filter_tier === 'pro' ? 'selected' : ''; ?>>Pro</option>
                            <option value="boss" <?php echo $filter_tier === 'boss' ? 'selected' : ''; ?>>Boss</option>
                            <option value="agency" <?php echo $filter_tier === 'agency' ? 'selected' : ''; ?>>Agency</option>
                        </select>
                        <select name="status">
                            <option value="">Alle statussen</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Actief</option>
                            <option value="suspended" <?php echo $filter_status === 'suspended' ? 'selected' : ''; ?>>Opgeschort</option>
                            <option value="expired" <?php echo $filter_status === 'expired' ? 'selected' : ''; ?>>Verlopen</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    </form>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Sleutel</th>
                            <th>Tier</th>
                            <th>E-mail</th>
                            <th>Status</th>
                            <th>Billing</th>
                            <th>Verloopt</th>
                            <th>Aangemaakt</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $licenses ) ) : ?>
                            <tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:32px;">Geen licenties gevonden.</td></tr>
                        <?php else : ?>
                            <?php foreach ( $licenses as $lic ) : ?>
                                <tr>
                                    <td><span class="mono"><?php echo htmlspecialchars( $lic['license_key'] ); ?></span></td>
                                    <td><?php echo $tier_badges[ $lic['tier'] ] ?? $lic['tier']; ?></td>
                                    <td><?php echo htmlspecialchars( $lic['email'] ); ?></td>
                                    <td><?php echo $status_badges[ $lic['status'] ] ?? $lic['status']; ?></td>
                                    <td><?php echo htmlspecialchars( $lic['billing_type'] ); ?></td>
                                    <td><?php echo $lic['expires_at'] ? date( 'd-m-Y', strtotime( $lic['expires_at'] ) ) : '∞'; ?></td>
                                    <td><?php echo date( 'd-m-Y', strtotime( $lic['created_at'] ) ); ?></td>
                                    <td style="white-space:nowrap;">
                                        <a href="<?php echo ADMIN_BASE; ?>/?action=activations&id=<?php echo $lic['id']; ?>" class="btn btn-sm" style="background:#e0f2fe;color:#0369a1;">Sites</a>
                                        <?php if ( $lic['status'] === 'active' ) : ?>
                                            <a href="<?php echo ADMIN_BASE; ?>/?action=toggle_status&id=<?php echo $lic['id']; ?>&new_status=suspended&csrf=<?php echo $csrf; ?>"
                                               class="btn btn-sm btn-warning"
                                               onclick="return confirm('Licentie opschorten?')">Opschorten</a>
                                        <?php else : ?>
                                            <a href="<?php echo ADMIN_BASE; ?>/?action=toggle_status&id=<?php echo $lic['id']; ?>&new_status=active&csrf=<?php echo $csrf; ?>"
                                               class="btn btn-sm btn-success">Heractiveren</a>
                                        <?php endif; ?>
                                        <a href="<?php echo ADMIN_BASE; ?>/?action=delete&id=<?php echo $lic['id']; ?>&csrf=<?php echo $csrf; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Licentie permanent verwijderen? Dit kan niet ongedaan worden gemaakt.')">Verwijder</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function render_activations( $lic, $acts ) {
    ?>
    <!DOCTYPE html>
    <html lang="nl">
    <head>
        <meta charset="UTF-8">
        <title>Activaties — <?php echo htmlspecialchars( $lic['license_key'] ); ?></title>
        <style>
            body { font-family: -apple-system, sans-serif; background: #f8fafc; margin: 0; padding: 32px; color: #1e293b; }
            .card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; max-width: 800px; margin: 0 auto; }
            .card-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
            h2 { margin: 0; font-size: 17px; }
            table { width: 100%; border-collapse: collapse; }
            th { padding: 12px 16px; text-align: left; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; }
            td { padding: 14px 16px; font-size: 13px; border-bottom: 1px solid #f8fafc; }
            .back { display: inline-block; margin-bottom: 20px; color: #3b82f6; text-decoration: none; font-size: 13px; }
        </style>
    </head>
    <body>
        <a href="<?php echo ADMIN_BASE; ?>/" class="back">← Terug naar dashboard</a>
        <div class="card">
            <div class="card-header">
                <h2>Activaties voor: <code><?php echo htmlspecialchars( $lic['license_key'] ); ?></code></h2>
                <p style="margin:4px 0 0;font-size:13px;color:#64748b;">
                    <?php echo htmlspecialchars( $lic['email'] ); ?> &bull;
                    Max activaties: <?php echo $lic['max_activations']; ?> &bull;
                    Huidig: <?php echo count( $acts ); ?>
                </p>
            </div>
            <?php if ( empty( $acts ) ) : ?>
                <p style="padding:32px;text-align:center;color:#94a3b8;">Geen actieve activaties.</p>
            <?php else : ?>
                <table>
                    <thead><tr><th>Domein</th><th>Plugin versie</th><th>Laatste activiteit</th><th>IP</th></tr></thead>
                    <tbody>
                        <?php foreach ( $acts as $a ) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars( $a['domain'] ); ?></td>
                                <td><?php echo htmlspecialchars( $a['plugin_ver'] ); ?></td>
                                <td><?php echo date( 'd-m-Y H:i', strtotime( $a['last_seen'] ) ); ?></td>
                                <td><?php echo htmlspecialchars( $a['ip'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
