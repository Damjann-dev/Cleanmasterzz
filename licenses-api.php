<?php
/**
 * Cleanmasterzz License API — WordPress root wrapper
 *
 * Dit bestand staat in de WordPress root zodat de standaard nginx PHP-handler
 * het kan uitvoeren. Het delegeert alle logica naar de echte API in /var/www/licenses/.
 *
 * Deploy: kopieer naar /var/www/html/licenses-api.php op de server.
 */

header( 'Content-Type: application/json' );
header( 'X-Content-Type-Options: nosniff' );

require_once '/var/www/licenses/config.php';

if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
    http_response_code( 405 );
    echo json_encode( array( 'error' => 'Alleen POST methode toegestaan.' ) );
    exit;
}

// Endpoint bepalen: eerst via URL-pad, daarna via ?ep= query string
$uri      = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$parts    = explode( '/', trim( $uri, '/' ) );
$last     = end( $parts );
$endpoint = ( $last && $last !== 'licenses-api.php' ) ? $last : ( $_GET['ep'] ?? '' );

// Maak endpoint beschikbaar voor de API handler
$_GET['ep'] = $endpoint;

require_once '/var/www/licenses/api/index.php';
