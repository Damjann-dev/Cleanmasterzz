<?php
/**
 * Cleanmasterzz License Server — REST API
 *
 * Routes:
 *   POST /api/validate    → Valideer key + domain
 *   POST /api/activate    → Activeer key op domain
 *   POST /api/deactivate  → Deactiveer key van domain
 */

header( 'Content-Type: application/json' );
header( 'X-Content-Type-Options: nosniff' );

require_once dirname( __DIR__ ) . '/config.php';

// ─── Rate limiting (max 30 requests per minuut per IP) ────────────────────
$rate_file = sys_get_temp_dir() . '/cmls_rate_' . md5( $_SERVER['REMOTE_ADDR'] ?? '' );
$rate_data = @file_get_contents( $rate_file );
$rate_data = $rate_data ? json_decode( $rate_data, true ) : array( 'count' => 0, 'window' => time() );

if ( time() - $rate_data['window'] > 60 ) {
    $rate_data = array( 'count' => 0, 'window' => time() );
}
$rate_data['count']++;
file_put_contents( $rate_file, json_encode( $rate_data ) );

if ( $rate_data['count'] > 30 ) {
    http_response_code( 429 );
    echo json_encode( array( 'valid' => false, 'message' => 'Rate limit overschreden.' ) );
    exit;
}

// ─── Routing ──────────────────────────────────────────────────────────────
$request_uri = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$path_parts  = explode( '/', trim( $request_uri, '/' ) );
$endpoint    = end( $path_parts );

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    http_response_code( 405 );
    echo json_encode( array( 'error' => 'Alleen POST methode toegestaan.' ) );
    exit;
}

// ─── Input lezen ──────────────────────────────────────────────────────────
$body  = file_get_contents( 'php://input' );
$input = json_decode( $body, true );

if ( ! is_array( $input ) ) {
    http_response_code( 400 );
    echo json_encode( array( 'valid' => false, 'message' => 'Ongeldige JSON body.' ) );
    exit;
}

$license_key = strtoupper( preg_replace( '/[^A-Z0-9-]/', '', $input['license_key'] ?? '' ) );
$domain      = preg_replace( '/[^a-z0-9.\-]/', '', strtolower( $input['domain'] ?? '' ) );
$plugin_ver  = preg_replace( '/[^0-9.\-a-z]/', '', $input['plugin_ver'] ?? '' );
$ip          = $_SERVER['REMOTE_ADDR'] ?? '';

// ─── DB verbinding ────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        )
    );
} catch ( PDOException $e ) {
    http_response_code( 503 );
    echo json_encode( array( 'valid' => false, 'message' => 'Database niet bereikbaar.' ) );
    exit;
}

// ─── Helpers ─────────────────────────────────────────────────────────────
function log_request( $pdo, $endpoint, $license_key, $domain, $ip, $result, $message = '' ) {
    try {
        $stmt = $pdo->prepare( '
            INSERT INTO api_logs (endpoint, license_key, domain, ip, result, message)
            VALUES (:endpoint, :license_key, :domain, :ip, :result, :message)
        ' );
        $stmt->execute( array(
            ':endpoint'    => $endpoint,
            ':license_key' => $license_key ?: null,
            ':domain'      => $domain ?: null,
            ':ip'          => $ip,
            ':result'      => $result,
            ':message'     => substr( $message, 0, 255 ),
        ) );
    } catch ( Exception $e ) {
        // Log fout negeren — niet kritiek
    }
}

function get_license( $pdo, $license_key ) {
    $stmt = $pdo->prepare( 'SELECT * FROM licenses WHERE license_key = :key LIMIT 1' );
    $stmt->execute( array( ':key' => $license_key ) );
    return $stmt->fetch();
}

function build_response( $license, $domain ) {
    return array(
        'valid'        => true,
        'tier'         => $license['tier'],
        'status'       => $license['status'],
        'expires_at'   => $license['expires_at'],
        'domain'       => $domain,
        'billing_type' => $license['billing_type'],
        'message'      => 'Licentie actief.',
    );
}

// ─── ENDPOINT: validate ────────────────────────────────────────────────────
if ( $endpoint === 'validate' ) {
    if ( ! $license_key || ! $domain ) {
        echo json_encode( array( 'valid' => false, 'message' => 'Sleutel en domein zijn verplicht.' ) );
        exit;
    }

    $license = get_license( $pdo, $license_key );

    if ( ! $license ) {
        log_request( $pdo, 'validate', $license_key, $domain, $ip, 'fail', 'Sleutel niet gevonden' );
        echo json_encode( array( 'valid' => false, 'message' => 'Licentiesleutel niet gevonden.' ) );
        exit;
    }

    if ( $license['status'] !== 'active' ) {
        log_request( $pdo, 'validate', $license_key, $domain, $ip, 'fail', 'Licentie inactief: ' . $license['status'] );
        echo json_encode( array( 'valid' => false, 'message' => 'Licentie is ' . $license['status'] . '.' ) );
        exit;
    }

    // Verloopdatum controleren
    if ( $license['expires_at'] && strtotime( $license['expires_at'] ) < time() ) {
        $pdo->prepare( "UPDATE licenses SET status = 'expired' WHERE id = :id" )
            ->execute( array( ':id' => $license['id'] ) );
        log_request( $pdo, 'validate', $license_key, $domain, $ip, 'fail', 'Verlopen' );
        echo json_encode( array( 'valid' => false, 'message' => 'Licentie is verlopen.' ) );
        exit;
    }

    // Domain check — versoepeld: domein mag ook subdomain zijn van het geregistreerde domein
    $activation = null;
    $stmt = $pdo->prepare( 'SELECT * FROM license_activations WHERE license_id = :id AND domain = :domain LIMIT 1' );
    $stmt->execute( array( ':id' => $license['id'], ':domain' => $domain ) );
    $activation = $stmt->fetch();

    if ( ! $activation ) {
        log_request( $pdo, 'validate', $license_key, $domain, $ip, 'fail', 'Domein niet geactiveerd' );
        echo json_encode( array( 'valid' => false, 'message' => 'Dit domein is niet geactiveerd voor deze sleutel. Activeer eerst via het plugin admin.' ) );
        exit;
    }

    // Update last_seen
    $pdo->prepare( 'UPDATE license_activations SET last_seen = NOW(), plugin_ver = :ver WHERE id = :id' )
        ->execute( array( ':ver' => $plugin_ver, ':id' => $activation['id'] ) );

    log_request( $pdo, 'validate', $license_key, $domain, $ip, 'success', 'Geldig' );
    echo json_encode( build_response( $license, $domain ) );
    exit;
}

// ─── ENDPOINT: activate ────────────────────────────────────────────────────
if ( $endpoint === 'activate' ) {
    if ( ! $license_key || ! $domain ) {
        echo json_encode( array( 'valid' => false, 'message' => 'Sleutel en domein zijn verplicht.' ) );
        exit;
    }

    $license = get_license( $pdo, $license_key );

    if ( ! $license ) {
        log_request( $pdo, 'activate', $license_key, $domain, $ip, 'fail', 'Sleutel niet gevonden' );
        echo json_encode( array( 'valid' => false, 'message' => 'Licentiesleutel niet gevonden.' ) );
        exit;
    }

    if ( $license['status'] !== 'active' ) {
        log_request( $pdo, 'activate', $license_key, $domain, $ip, 'fail', 'Status: ' . $license['status'] );
        echo json_encode( array( 'valid' => false, 'message' => 'Licentie is niet actief (' . $license['status'] . ').' ) );
        exit;
    }

    // Verloopdatum controleren
    if ( $license['expires_at'] && strtotime( $license['expires_at'] ) < time() ) {
        log_request( $pdo, 'activate', $license_key, $domain, $ip, 'fail', 'Verlopen' );
        echo json_encode( array( 'valid' => false, 'message' => 'Licentie is verlopen.' ) );
        exit;
    }

    // Controleer of domein al geactiveerd is
    $stmt = $pdo->prepare( 'SELECT id FROM license_activations WHERE license_id = :id AND domain = :domain LIMIT 1' );
    $stmt->execute( array( ':id' => $license['id'], ':domain' => $domain ) );
    $existing = $stmt->fetch();

    if ( $existing ) {
        // Heractivatie: update last_seen
        $pdo->prepare( 'UPDATE license_activations SET last_seen = NOW(), plugin_ver = :ver, ip = :ip WHERE id = :id' )
            ->execute( array( ':ver' => $plugin_ver, ':ip' => $ip, ':id' => $existing['id'] ) );
        log_request( $pdo, 'activate', $license_key, $domain, $ip, 'success', 'Heractivatie' );
        echo json_encode( build_response( $license, $domain ) );
        exit;
    }

    // Controleer max activaties
    $stmt = $pdo->prepare( 'SELECT COUNT(*) as cnt FROM license_activations WHERE license_id = :id' );
    $stmt->execute( array( ':id' => $license['id'] ) );
    $count = intval( $stmt->fetch()['cnt'] );

    if ( $count >= intval( $license['max_activations'] ) ) {
        log_request( $pdo, 'activate', $license_key, $domain, $ip, 'fail', 'Max activaties bereikt' );
        echo json_encode( array( 'valid' => false, 'message' => 'Maximum aantal activaties (' . $license['max_activations'] . ') bereikt. Deactiveer een andere site eerst.' ) );
        exit;
    }

    // Nieuwe activatie opslaan
    $stmt = $pdo->prepare( '
        INSERT INTO license_activations (license_id, domain, plugin_ver, ip)
        VALUES (:lid, :domain, :ver, :ip)
    ' );
    $stmt->execute( array(
        ':lid'    => $license['id'],
        ':domain' => $domain,
        ':ver'    => $plugin_ver,
        ':ip'     => $ip,
    ) );

    log_request( $pdo, 'activate', $license_key, $domain, $ip, 'success', 'Nieuw geactiveerd' );
    echo json_encode( build_response( $license, $domain ) );
    exit;
}

// ─── ENDPOINT: deactivate ─────────────────────────────────────────────────
if ( $endpoint === 'deactivate' ) {
    if ( ! $license_key || ! $domain ) {
        echo json_encode( array( 'success' => false, 'message' => 'Sleutel en domein zijn verplicht.' ) );
        exit;
    }

    $license = get_license( $pdo, $license_key );

    if ( $license ) {
        $pdo->prepare( 'DELETE FROM license_activations WHERE license_id = :lid AND domain = :domain' )
            ->execute( array( ':lid' => $license['id'], ':domain' => $domain ) );
    }

    log_request( $pdo, 'deactivate', $license_key, $domain, $ip, 'success', 'Gedeactiveerd' );
    echo json_encode( array( 'success' => true, 'message' => 'Licentie gedeactiveerd.' ) );
    exit;
}

// ─── Onbekend endpoint ────────────────────────────────────────────────────
http_response_code( 404 );
echo json_encode( array( 'error' => 'Endpoint niet gevonden.' ) );
