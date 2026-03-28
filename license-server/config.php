<?php
/**
 * Cleanmasterzz License Server — Configuratie
 *
 * AANPASSEN voor productie:
 *  - DB_* instellingen
 *  - ADMIN_PASSWORD (gebruik password_hash)
 *  - SECRET_KEY (willekeurige lange string)
 */

define( 'DB_HOST',     'localhost' );
define( 'DB_NAME',     'cmcalc_licenses' );
define( 'DB_USER',     'cmcalc_user' );
define( 'DB_PASS',     'CHANGE_THIS_PASSWORD' );
define( 'DB_CHARSET',  'utf8mb4' );

// Admin panel wachtwoord (bcrypt hash)
// Genereer nieuw: php -r "echo password_hash('jouwwachtwoord', PASSWORD_BCRYPT);"
define( 'ADMIN_PASSWORD_HASH', '$2y$10$CHANGE_THIS_TO_A_REAL_BCRYPT_HASH' );
define( 'ADMIN_USERNAME',      'admin' );

// Geheime sleutel voor API request signing
define( 'SECRET_KEY', 'CHANGE_THIS_TO_A_LONG_RANDOM_SECRET_KEY_AT_LEAST_64_CHARS' );

// License server versie
define( 'SERVER_VERSION', '1.0.0' );

// Tijdzone
date_default_timezone_set( 'Europe/Amsterdam' );

// Debug mode (ALTIJD false in productie)
define( 'DEBUG_MODE', false );

// Sessie naam
define( 'SESSION_NAME', 'cmcalc_ls_session' );

// URL base voor admin panel (geen trailing slash)
// Pas aan als de server op een ander pad draait
define( 'ADMIN_BASE', '/licenses/admin' );
