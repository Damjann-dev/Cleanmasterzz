<?php
/**
 * Cleanmasterzz License Server — Database Migratie
 *
 * Voer eenmalig uit na update om nieuwe kolommen toe te voegen.
 * Daarna verwijderen: rm /var/www/licenses/migrate.php
 */

define( 'MIGRATE_TOKEN', 'CHANGE_THIS_MIGRATE_TOKEN' );

if ( ( $_GET['token'] ?? '' ) !== MIGRATE_TOKEN ) {
    http_response_code( 403 );
    die( 'Toegang geweigerd. Voeg ?token=CHANGE_THIS_MIGRATE_TOKEN toe.' );
}

require_once __DIR__ . '/config.php';

$log = array();

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS,
        array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION )
    );

    // Helper: kolom bestaat al?
    function column_exists( $pdo, $table, $column ) {
        $stmt = $pdo->query( "SHOW COLUMNS FROM `$table` LIKE '$column'" );
        return $stmt->rowCount() > 0;
    }

    // ── 1. tier uitbreiden met 'free' ─────────────────────────────────
    $pdo->exec( "ALTER TABLE licenses MODIFY COLUMN tier ENUM('free','pro','boss','agency') NOT NULL DEFAULT 'free'" );
    $log[] = "✅ tier enum uitgebreid met 'free'";

    // ── 2. name kolom ─────────────────────────────────────────────────
    if ( ! column_exists( $pdo, 'licenses', 'name' ) ) {
        $pdo->exec( "ALTER TABLE licenses ADD COLUMN name VARCHAR(255) NOT NULL DEFAULT '' AFTER email" );
        $log[] = "✅ Kolom 'name' toegevoegd";
    } else {
        $log[] = "⏭  Kolom 'name' bestaat al";
    }

    // ── 3. max_services kolom ─────────────────────────────────────────
    if ( ! column_exists( $pdo, 'licenses', 'max_services' ) ) {
        $pdo->exec( "ALTER TABLE licenses ADD COLUMN max_services INT NOT NULL DEFAULT 3 AFTER max_activations" );
        $log[] = "✅ Kolom 'max_services' toegevoegd";

        // Vul standaard waarden in per tier
        $pdo->exec( "UPDATE licenses SET max_services = 10  WHERE tier = 'pro'" );
        $pdo->exec( "UPDATE licenses SET max_services = 999 WHERE tier IN ('boss','agency')" );
        $log[] = "✅ max_services ingesteld per tier (pro=10, boss/agency=999)";
    } else {
        $log[] = "⏭  Kolom 'max_services' bestaat al";
    }

    // ── 4. features kolom (JSON) ──────────────────────────────────────
    if ( ! column_exists( $pdo, 'licenses', 'features' ) ) {
        $pdo->exec( "ALTER TABLE licenses ADD COLUMN features TEXT NULL AFTER max_services" );
        $log[] = "✅ Kolom 'features' (JSON) toegevoegd";
    } else {
        $log[] = "⏭  Kolom 'features' bestaat al";
    }

    // ── 5. updated_at kolom ───────────────────────────────────────────
    if ( ! column_exists( $pdo, 'licenses', 'updated_at' ) ) {
        $pdo->exec( "ALTER TABLE licenses ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at" );
        $log[] = "✅ Kolom 'updated_at' toegevoegd";
    } else {
        $log[] = "⏭  Kolom 'updated_at' bestaat al";
    }

    // ── 6. billing_type default aanpassen ─────────────────────────────
    $pdo->exec( "ALTER TABLE licenses MODIFY COLUMN billing_type ENUM('monthly','yearly','lifetime') NOT NULL DEFAULT 'lifetime'" );
    $log[] = "✅ billing_type default naar 'lifetime'";

    // ── 7. Index op tier toevoegen ────────────────────────────────────
    try {
        $pdo->exec( "ALTER TABLE licenses ADD INDEX idx_tier (tier)" );
        $log[] = "✅ Index idx_tier toegevoegd";
    } catch ( PDOException $e ) {
        $log[] = "⏭  Index idx_tier bestaat al";
    }

} catch ( PDOException $e ) {
    http_response_code( 500 );
    echo '<pre style="color:red;">FOUT: ' . htmlspecialchars( $e->getMessage() ) . '</pre>';
    exit;
}

echo '<pre style="font-family:monospace;padding:20px;background:#0d1220;color:#f1f5f9;">';
echo "═══════════════════════════════════════════\n";
echo "  Cleanmasterzz License Server — Migratie\n";
echo "═══════════════════════════════════════════\n\n";
foreach ( $log as $line ) {
    echo $line . "\n";
}
echo "\n⚠️  VERWIJDER migrate.php nu direct!\n";
echo "   rm /var/www/licenses/migrate.php\n";
echo '</pre>';
