<?php
/**
 * CleanMasterzz Admin Portal – Configuratie
 * Kopieer dit bestand naar config.php en vul de gegevens in.
 * config.php staat in .gitignore en mag NOOIT worden gecommit.
 */

// WordPress site URL (geen trailing slash)
define( 'WP_SITE_URL', 'https://cleanmasterzz.nl' );

// WordPress gebruikersnaam + Application Password
// Aanmaken via: WP Admin → Gebruikers → Jouw profiel → Application Passwords
define( 'WP_API_USER', 'admin' );
define( 'WP_API_PASS', 'xxxx xxxx xxxx xxxx xxxx xxxx' );

// Portal login wachtwoord (bcrypt hash)
// Genereer: php -r "echo password_hash('JouwWachtwoord', PASSWORD_BCRYPT);"
define( 'PORTAL_PASSWORD_HASH', '$2y$10$CHANGE_THIS_TO_A_REAL_BCRYPT_HASH' );
define( 'PORTAL_USERNAME',      'admin' );

// Sessie naam
define( 'PORTAL_SESSION', 'cmcalc_portal_session' );

// Tijdzone
date_default_timezone_set( 'Europe/Amsterdam' );
