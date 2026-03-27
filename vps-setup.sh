#!/bin/bash
# CleanMasterzz VPS Setup Script
# Ubuntu 20.04/22.04/24.04 — LEMP + WordPress + License Server
# Uitvoeren als root: bash vps-setup.sh

set -e
GREEN='\033[0;32m'; RED='\033[0;31m'; NC='\033[0m'
log() { echo -e "${GREEN}[$(date +%H:%M:%S)]${NC} $1"; }
err() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

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
WP_DIR="/var/www/html"
LICENSE_DIR="/var/www/licenses"
BACKUP_DIR="/backups"

log "=== CleanMasterzz VPS Setup gestart ==="

export DEBIAN_FRONTEND=noninteractive
log "Stap 1: Systeem updaten..."
apt-get update -qq && apt-get upgrade -y -qq
apt-get install -y -qq curl wget git unzip zip ufw fail2ban software-properties-common apt-transport-https ca-certificates

log "Stap 2: Nginx installeren..."
apt-get install -y -qq nginx
systemctl enable nginx && systemctl start nginx

log "Stap 3: PHP 8.3 installeren..."
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq php8.3-fpm php8.3-cli php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 128M/' /etc/php/8.3/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 128M/' /etc/php/8.3/fpm/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 512M/' /etc/php/8.3/fpm/php.ini
systemctl restart php8.3-fpm && systemctl enable php8.3-fpm

log "Stap 4: MySQL installeren..."
apt-get install -y -qq mysql-server
mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${MYSQL_ROOT_PASS}';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "CREATE DATABASE ${WP_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "CREATE USER '${WP_DB_USER}'@'localhost' IDENTIFIED BY '${WP_DB_PASS}';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "GRANT ALL PRIVILEGES ON ${WP_DB_NAME}.* TO '${WP_DB_USER}'@'localhost';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "CREATE DATABASE ${LICENSE_DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "CREATE USER '${LICENSE_DB_USER}'@'localhost' IDENTIFIED BY '${LICENSE_DB_PASS}';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "GRANT ALL PRIVILEGES ON ${LICENSE_DB_NAME}.* TO '${LICENSE_DB_USER}'@'localhost';"
mysql -u root -p"${MYSQL_ROOT_PASS}" -e "FLUSH PRIVILEGES;"
systemctl enable mysql

log "Stap 5: WP-CLI installeren..."
curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar && mv wp-cli.phar /usr/local/bin/wp

log "Stap 6: WordPress installeren..."
mkdir -p ${WP_DIR} && cd ${WP_DIR}
wp core download --allow-root --locale=nl_NL
wp config create --dbname="${WP_DB_NAME}" --dbuser="${WP_DB_USER}" --dbpass="${WP_DB_PASS}" --dbhost="localhost" --locale="nl_NL" --allow-root
wp config set WP_DEBUG false --allow-root
wp config set DISALLOW_FILE_EDIT true --allow-root
wp config set WP_MEMORY_LIMIT '256M' --allow-root
wp core install --url="http://${SERVER_IP}" --title="CleanMasterzz" --admin_user="${WP_ADMIN_USER}" --admin_password="${WP_ADMIN_PASS}" --admin_email="${WP_ADMIN_EMAIL}" --skip-email --allow-root
wp option update timezone_string "Europe/Amsterdam" --allow-root
wp rewrite structure '/%postname%/' --allow-root
wp rewrite flush --allow-root
wp post delete 1 2 --force --allow-root 2>/dev/null || true
wp plugin delete hello akismet --allow-root 2>/dev/null || true

log "Stap 7: Custom thema aanmaken..."
THEME_DIR="${WP_DIR}/wp-content/themes/cleanmasterzz"
mkdir -p "${THEME_DIR}"
printf '/*\nTheme Name: CleanMasterzz\nVersion: 1.0.0\n*/' > "${THEME_DIR}/style.css"
printf '<?php get_header(); get_footer();' > "${THEME_DIR}/index.php"
wp theme activate cleanmasterzz --allow-root

log "Stap 8: License server installeren..."
mkdir -p ${LICENSE_DIR}
cat > "${LICENSE_DIR}/config.php" << LICCONFIG
<?php
define('LICENSE_DB_HOST', 'localhost');
define('LICENSE_DB_NAME', '${LICENSE_DB_NAME}');
define('LICENSE_DB_USER', '${LICENSE_DB_USER}');
define('LICENSE_DB_PASS', '${LICENSE_DB_PASS}');
define('LICENSE_ADMIN_PASS', password_hash('Damjan1!', PASSWORD_DEFAULT));
define('LICENSE_SECRET_KEY', '$(openssl rand -hex 32)');
LICCONFIG

log "Stap 9: Nginx configureren..."
cat > /etc/nginx/sites-available/cleanmasterzz << 'NGINXEOF'
server {
    listen 80;
    server_name _;
    root /var/www/html;
    index index.php index.html;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }
    location ~* \.(js|css|png|jpg|ico|svg|woff2)$ { expires 1y; add_header Cache-Control "public, immutable"; }
    location ~ /\.ht { deny all; }
    location ~ /wp-config.php { deny all; }
}
NGINXEOF
rm -f /etc/nginx/sites-enabled/default
ln -sf /etc/nginx/sites-available/cleanmasterzz /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

log "Stap 10: Firewall..."
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

log "Stap 11: Fail2ban..."
systemctl enable fail2ban && systemctl restart fail2ban

log "Stap 12: Permissies..."
chown -R www-data:www-data ${WP_DIR}
find ${WP_DIR} -type d -exec chmod 755 {} \;
find ${WP_DIR} -type f -exec chmod 644 {} \;
chmod 600 ${WP_DIR}/wp-config.php
chown -R www-data:www-data ${LICENSE_DIR}

cat > /root/cleanmasterzz-credentials.txt << CREDS
=== CleanMasterzz Credentials ===
Aangemaakt: $(date)
MySQL Root: ${MYSQL_ROOT_PASS}
WP DB: ${WP_DB_NAME} / ${WP_DB_USER} / ${WP_DB_PASS}
WP Admin: ${WP_ADMIN_USER} / ${WP_ADMIN_PASS}
WP URL: http://${SERVER_IP}
License DB: ${LICENSE_DB_NAME} / ${LICENSE_DB_USER} / ${LICENSE_DB_PASS}
CREDS
chmod 600 /root/cleanmasterzz-credentials.txt

log ""
log "=========================================="
log "  INSTALLATIE VOLTOOID!"
log "=========================================="
log "  WordPress: http://${SERVER_IP}"
log "  WP Admin:  http://${SERVER_IP}/wp-admin"
log "  Login:     ${WP_ADMIN_USER} / ${WP_ADMIN_PASS}"
log "  Credentials opgeslagen in: /root/cleanmasterzz-credentials.txt"
