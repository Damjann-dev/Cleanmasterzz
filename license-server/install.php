<?php
/**
 * Cleanmasterzz License Server — Installatiescript
 *
 * EENMALIG uitvoeren via browser na deployment:
 *   https://licenses.jouwdomein.nl/install.php
 *
 * VERWIJDER dit bestand daarna direct!
 */

// Beveiligingscode — aanpassen voor installatie
define( 'INSTALL_TOKEN', 'CHANGE_THIS_INSTALL_TOKEN' );

if ( ( $_GET['token'] ?? '' ) !== INSTALL_TOKEN ) {
    http_response_code( 403 );
    die( 'Toegang geweigerd. Voeg ?token=INSTALL_TOKEN toe aan de URL.' );
}

require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        array( PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION )
    );

    // Database aanmaken
    $pdo->exec( 'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );
    $pdo->exec( 'USE `' . DB_NAME . '`' );

    // ── Tabel: licenses ───────────────────────────────────────────────
    $pdo->exec( "
        CREATE TABLE IF NOT EXISTS licenses (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            license_key     VARCHAR(30) NOT NULL UNIQUE,
            tier            ENUM('free','pro','boss','agency') NOT NULL DEFAULT 'free',
            email           VARCHAR(255) NOT NULL,
            name            VARCHAR(255) NOT NULL DEFAULT '',
            status          ENUM('active','suspended','expired') NOT NULL DEFAULT 'active',
            billing_type    ENUM('monthly','yearly','lifetime') NOT NULL DEFAULT 'lifetime',
            max_activations INT NOT NULL DEFAULT 1,
            max_services    INT NOT NULL DEFAULT 3,
            features        TEXT NULL,
            expires_at      DATETIME NULL,
            notes           TEXT,
            created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (license_key),
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_tier (tier)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    " );

    // ── Tabel: license_activations ────────────────────────────────────
    $pdo->exec( "
        CREATE TABLE IF NOT EXISTS license_activations (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            license_id   INT NOT NULL,
            domain       VARCHAR(255) NOT NULL,
            activated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            plugin_ver   VARCHAR(20),
            ip           VARCHAR(45),
            INDEX idx_license (license_id),
            INDEX idx_domain (domain),
            FOREIGN KEY (license_id) REFERENCES licenses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    " );

    // ── Tabel: api_logs ──────────────────────────────────────────────
    $pdo->exec( "
        CREATE TABLE IF NOT EXISTS api_logs (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            endpoint    VARCHAR(50) NOT NULL,
            license_key VARCHAR(30),
            domain      VARCHAR(255),
            ip          VARCHAR(45),
            result      ENUM('success','fail','error') NOT NULL,
            message     VARCHAR(255),
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at),
            INDEX idx_license (license_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    " );

    echo '<pre style="font-family:monospace;padding:20px;">';
    echo "✅ Database aangemaakt: " . DB_NAME . "\n";
    echo "✅ Tabel 'licenses' aangemaakt\n";
    echo "✅ Tabel 'license_activations' aangemaakt\n";
    echo "✅ Tabel 'api_logs' aangemaakt\n\n";
    echo "⚠️  VERWIJDER install.php nu direct van de server!\n";
    echo '   rm /var/www/licenses/install.php' . "\n\n";
    echo "🔑 Installatie voltooid. Ga naar /admin/ om in te loggen.\n";
    echo '</pre>';

} catch ( PDOException $e ) {
    http_response_code( 500 );
    echo '<pre style="color:red;">FOUT: ' . htmlspecialchars( $e->getMessage() ) . '</pre>';
}
