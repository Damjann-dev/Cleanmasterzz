#!/bin/bash
# CleanMasterzz VPS Setup Script
# Ubuntu 24.04 — LEMP + WordPress + License Server
# Uitvoeren als root: bash vps-setup.sh

set -e
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
log() { echo -e "${GREEN}[$(date +%H:%M:%S)]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
err() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# ============================================================
# CONFIGURATIE
# ============================================================
SERVER_IP="185.228.82.252"
WP_DB_NAME="cleanmasterzz_wp"
WP_DB_USER="cm_wp_user"
WP_DB_PASS="$(openssl rand -base64 32 | tr -d '=/+')"
LICENSE_DB_NAME="cleanmasterzz_licenses"
LICENSE_DB_USER="cm_lic_user"
LICENSE_DB_PASS="$(openssl rand -base64 32 | tr -d '=/+')"
MYSQL_ROOT_PASS="$(openssl rand -base64 32 | tr -d '=/+')"
WP_ADMIN_USER="damjan"
WP_ADMIN_PASS="Damjan1!"
WP_ADMIN_EMAIL="admin@cleanmasterzz.nl"
LICENSE_ADMIN_PASS="Damjan1!"
WP_DIR="/var/www/html"
LICENSE_DIR="/var/www/licenses"
BACKUP_DIR="/backups"

log "=== CleanMasterzz VPS Setup gestart ==="
log "Server IP: $SERVER_IP"

# ============================================================
# STAP 1: SYSTEEM UPDATE
# ============================================================
log "Stap 1: Systeem updaten..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq && apt-get upgrade -y -qq
apt-get install -y -qq \
  curl wget git unzip zip \
  ufw fail2ban \
  software-properties-common \
  apt-transport-https ca-certificates

# ============================================================
# STAP 2: NGINX
# ============================================================
log "Stap 2: Nginx installeren..."
apt-get install -y -qq nginx
systemctl enable nginx
systemctl start nginx

# ============================================================
# STAP 3: PHP 8.3
# ============================================================
log "Stap 3: PHP 8.3 installeren..."
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq \
  php8.3-fpm php8.3-cli \
  php8.3-mysql php8.3-xml php8.3-mbstring \
  php8.3-curl php8.3-zip php8.3-gd \
  php8.3-intl php8.3-bcmath php8.3-soap \
  php8.3-imagick php8.3-redis

# PHP configuratie
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 128M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 128M/' /etc/php/8.3/fpm/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 512M/' /etc/php/8.3/fpm/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.3/fpm/php.ini

systemctl restart php8.3-fpm
systemctl enable php8.3-fpm

# ============================================================
# STAP 4: MYSQL 8
# ============================================================
log "Stap 4: MySQL 8 installeren..."
apt-get install -y -qq mysql-server

# Secure MySQL
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASS}';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "DELETE FROM mysql.user WHERE User='';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "DROP DATABASE IF EXISTS test;"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "FLUSH PRIVILEGES;"

# WordPress database
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "CREATE DATABASE ${WP_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "CREATE USER '${WP_DB_USER}'@'localhost' IDENTIFIED BY '${WP_DB_PASS}';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "GRANT ALL PRIVILEGES ON ${WP_DB_NAME}.* TO '${WP_DB_USER}'@'localhost';"

# License database
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "CREATE DATABASE ${LICENSE_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "CREATE USER '${LICENSE_DB_USER}'@'localhost' IDENTIFIED BY '${LICENSE_DB_PASS}';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "GRANT ALL PRIVILEGES ON ${LICENSE_DB_NAME}.* TO '${LICENSE_DB_USER}'@'localhost';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "FLUSH PRIVILEGES;"

systemctl enable mysql

# ============================================================
# STAP 5: WP-CLI
# ============================================================
log "Stap 5: WP-CLI installeren..."
curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp

# ============================================================
# STAP 6: WORDPRESS INSTALLEREN
# ============================================================
log "Stap 6: WordPress installeren..."
mkdir -p ${WP_DIR}
cd ${WP_DIR}
wp core download --allow-root --locale=nl_NL

# wp-config aanmaken
wp config create \
  --dbname="${WP_DB_NAME}" \
  --dbuser="${WP_DB_USER}" \
  --dbpass="${WP_DB_PASS}" \
  --dbhost="localhost" \
  --locale="nl_NL" \
  --allow-root

# Extra wp-config instellingen
wp config set WP_DEBUG false --allow-root
wp config set WP_DEBUG_LOG false --allow-root
wp config set DISALLOW_FILE_EDIT true --allow-root
wp config set WP_POST_REVISIONS 5 --raw --allow-root
wp config set AUTOSAVE_INTERVAL 300 --raw --allow-root
wp config set WP_MEMORY_LIMIT '256M' --allow-root

# WordPress installeren
wp core install \
  --url="http://${SERVER_IP}" \
  --title="CleanMasterzz" \
  --admin_user="${WP_ADMIN_USER}" \
  --admin_password="${WP_ADMIN_PASS}" \
  --admin_email="${WP_ADMIN_EMAIL}" \
  --skip-email \
  --allow-root

# Basis instellingen
wp option update blogdescription "Professionele offertesoftware voor schoonmaakbedrijven" --allow-root
wp option update timezone_string "Europe/Amsterdam" --allow-root
wp option update date_format "d-m-Y" --allow-root
wp option update time_format "H:i" --allow-root
wp option update start_of_week 1 --allow-root

# Permalinks
wp rewrite structure '/%postname%/' --allow-root
wp rewrite flush --allow-root

# Verwijder standaard content
wp post delete 1 --force --allow-root 2>/dev/null || true
wp post delete 2 --force --allow-root 2>/dev/null || true
wp plugin delete hello --allow-root 2>/dev/null || true
wp plugin delete akismet --allow-root 2>/dev/null || true

# ============================================================
# STAP 7: WORDPRESS THEMA (PLACEHOLDER — wordt vervangen)
# ============================================================
log "Stap 7: Custom thema aanmaken..."
THEME_DIR="${WP_DIR}/wp-content/themes/cleanmasterzz"
mkdir -p "${THEME_DIR}"

# Thema bestanden worden later gedeployed via git
cat > "${THEME_DIR}/style.css" << 'THEME_CSS'
/*
Theme Name: CleanMasterzz
Theme URI: https://cleanmasterzz.nl
Author: CleanMasterzz
Description: Premium WordPress thema voor CleanMasterzz
Version: 1.0.0
*/
THEME_CSS

cat > "${THEME_DIR}/index.php" << 'THEME_PHP'
<?php
// Placeholder - wordt vervangen door volledig thema
get_header();
get_footer();
THEME_PHP

wp theme activate cleanmasterzz --allow-root

# ============================================================
# STAP 8: LICENSE SERVER
# ============================================================
log "Stap 8: License server installeren..."
mkdir -p ${LICENSE_DIR}

# License server config
cat > "${LICENSE_DIR}/config.php" << LICCONFIG
<?php
define('LICENSE_DB_HOST', 'localhost');
define('LICENSE_DB_NAME', '${LICENSE_DB_NAME}');
define('LICENSE_DB_USER', '${LICENSE_DB_USER}');
define('LICENSE_DB_PASS', '${LICENSE_DB_PASS}');
define('LICENSE_ADMIN_PASS', password_hash('${LICENSE_ADMIN_PASS}', PASSWORD_DEFAULT));
define('LICENSE_SECRET_KEY', '$(openssl rand -hex 32)');
define('LICENSE_API_URL', 'http://${SERVER_IP}/licenses/api/');
LICCONFIG

log "License server config aangemaakt"

# ============================================================
# STAP 9: NGINX CONFIGURATIE
# ============================================================
log "Stap 9: Nginx configureren..."

# Hoofd WordPress config
cat > /etc/nginx/sites-available/cleanmasterzz << NGINXCONF
server {
    listen 80;
    server_name ${SERVER_IP} _;
    root ${WP_DIR};
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # Gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;

    # WordPress permalinks
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # PHP verwerking
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }

    # License server
    location /licenses/ {
        alias ${LICENSE_DIR}/;
        try_files \$uri \$uri/ /licenses/index.php?\$query_string;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
            fastcgi_param SCRIPT_FILENAME ${LICENSE_DIR}\$fastcgi_script_name;
        }
    }

    # Statische bestanden cachen
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        log_not_found off;
    }

    # WordPress admin rate limiting
    location = /wp-login.php {
        limit_req zone=login burst=5 nodelay;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }

    # Beveiligen van gevoelige bestanden
    location ~ /\.ht { deny all; }
    location ~ /wp-config.php { deny all; }
    location ~ /xmlrpc.php { deny all; }
    location ~ /wp-includes/.*\.php { deny all; }
}
NGINXCONF

# Rate limiting toevoegen aan nginx.conf
sed -i '/http {/a\\tlimit_req_zone $binary_remote_addr zone=login:10m rate=1r/s;' /etc/nginx/nginx.conf

# Site activeren
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/cleanmasterzz /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# ============================================================
# STAP 10: FIREWALL
# ============================================================
log "Stap 10: Firewall instellen..."
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# ============================================================
# STAP 11: FAIL2BAN
# ============================================================
log "Stap 11: Fail2ban instellen..."
cat > /etc/fail2ban/jail.local << 'F2B'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log

[nginx-http-auth]
enabled = true

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
action = iptables-multiport[name=nginx-limit-req, port="http,https"]
logpath = /var/log/nginx/error.log
F2B

systemctl enable fail2ban
systemctl restart fail2ban

# ============================================================
# STAP 12: AUTOMATISCHE BACKUPS
# ============================================================
log "Stap 12: Automatische backups instellen..."
mkdir -p ${BACKUP_DIR}

cat > /usr/local/bin/cm-backup.sh << BACKUP
#!/bin/bash
DATE=\$(date +%Y%m%d_%H%M%S)
BACKUP_PATH="${BACKUP_DIR}/\${DATE}"
mkdir -p "\${BACKUP_PATH}"

# Database backup
mysqldump -u root -p"${MYSQL_ROOT_PASS}" ${WP_DB_NAME} | gzip > "\${BACKUP_PATH}/wordpress_db.sql.gz"
mysqldump -u root -p"${MYSQL_ROOT_PASS}" ${LICENSE_DB_NAME} | gzip > "\${BACKUP_PATH}/licenses_db.sql.gz"

# Bestanden backup
tar -czf "\${BACKUP_PATH}/wordpress_files.tar.gz" ${WP_DIR}/wp-content/ 2>/dev/null

# Oude backups verwijderen (ouder dan 30 dagen)
find ${BACKUP_DIR} -type d -mtime +30 -exec rm -rf {} + 2>/dev/null

echo "Backup voltooid: \${BACKUP_PATH}"
BACKUP

chmod +x /usr/local/bin/cm-backup.sh

# Cron job: elke dag om 3:00
(crontab -l 2>/dev/null; echo "0 3 * * * /usr/local/bin/cm-backup.sh >> /var/log/cm-backup.log 2>&1") | crontab -

# ============================================================
# STAP 13: PERMISSIES
# ============================================================
log "Stap 13: Permissies instellen..."
chown -R www-data:www-data ${WP_DIR}
find ${WP_DIR} -type d -exec chmod 755 {} \;
find ${WP_DIR} -type f -exec chmod 644 {} \;
chmod 600 ${WP_DIR}/wp-config.php

chown -R www-data:www-data ${LICENSE_DIR}
chmod -R 755 ${LICENSE_DIR}
chmod 600 ${LICENSE_DIR}/config.php

# ============================================================
# KLAAR — SAMENVATTING
# ============================================================
log ""
log "=========================================="
log "  INSTALLATIE VOLTOOID!"
log "=========================================="
log ""
log "  WordPress:         http://${SERVER_IP}"
log "  WP Admin:          http://${SERVER_IP}/wp-admin"
log "  WP gebruiker:      ${WP_ADMIN_USER}"
log "  WP wachtwoord:     ${WP_ADMIN_PASS}"
log ""
log "  License Server:    http://${SERVER_IP}/licenses"
log "  License Admin:     http://${SERVER_IP}/licenses/admin"
log "  License wachtwoord: ${LICENSE_ADMIN_PASS}"
log ""
log "  Backups map:       ${BACKUP_DIR}"
log ""

# Credentials opslaan
cat > /root/cleanmasterzz-credentials.txt << CREDS
=== CleanMasterzz Server Credentials ===
Aangemaakt: $(date)

MySQL Root Password: ${MYSQL_ROOT_PASS}
WordPress DB: ${WP_DB_NAME} / ${WP_DB_USER} / ${WP_DB_PASS}
License DB: ${LICENSE_DB_NAME} / ${LICENSE_DB_USER} / ${LICENSE_DB_PASS}

WordPress Admin: ${WP_ADMIN_USER} / ${WP_ADMIN_PASS}
WordPress URL: http://${SERVER_IP}

License Server Admin: ${LICENSE_ADMIN_PASS}
License Server URL: http://${SERVER_IP}/licenses
CREDS

chmod 600 /root/cleanmasterzz-credentials.txt
log "Credentials opgeslagen in: /root/cleanmasterzz-credentials.txt"
log ""
log "Voer daarna uit: bash /root/deploy-theme.sh"
