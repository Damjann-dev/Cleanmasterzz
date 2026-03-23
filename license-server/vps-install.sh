#!/bin/bash
##############################################################################
# Cleanmasterzz — Complete VPS Installatiescript
# Ubuntu 24.04 LTS
#
# Gebruik:
#   chmod +x vps-install.sh
#   sudo ./vps-install.sh
#
# Dit script installeert:
#  1. Systeem updates + beveiligingstools
#  2. Nginx + PHP 8.2-FPM + MySQL 8.0
#  3. WP-CLI
#  4. WordPress
#  5. License Server
#  6. Let's Encrypt SSL
#  7. Automatische backups
#  8. Fail2ban + UFW
##############################################################################

set -euo pipefail

# ── CONFIGURATIE ─────────────────────────────────────────────────────────────
WP_DOMAIN="jouwdomein.nl"               # WordPress domein
WP_ADMIN_EMAIL="admin@jouwdomein.nl"    # WordPress admin e-mail
WP_ADMIN_USER="admin"                   # WordPress admin gebruikersnaam
WP_ADMIN_PASS="$(openssl rand -base64 18)"  # Auto-gegenereerd

LS_DOMAIN="licenses.jouwdomein.nl"      # License server domein

DB_WP_NAME="wordpress"
DB_WP_USER="wp_user"
DB_WP_PASS="$(openssl rand -base64 18)"
DB_LS_NAME="cmcalc_licenses"
DB_LS_USER="cmcalc_user"
DB_LS_PASS="$(openssl rand -base64 18)"
DB_ROOT_PASS="$(openssl rand -base64 24)"

WEB_DIR="/var/www"
WP_DIR="$WEB_DIR/html"
LS_DIR="$WEB_DIR/licenses"

# Kleuren
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log()    { echo -e "${GREEN}[✓] $1${NC}"; }
warn()   { echo -e "${YELLOW}[!] $1${NC}"; }
info()   { echo -e "${BLUE}[→] $1${NC}"; }
error()  { echo -e "${RED}[✗] $1${NC}"; exit 1; }

# Root check
[[ $EUID -ne 0 ]] && error "Dit script moet als root worden uitgevoerd."

info "Cleanmasterzz VPS Installatie gestart..."
echo ""

##############################################################################
# 1. SYSTEEM UPDATES
##############################################################################
info "Stap 1/8: Systeem bijwerken..."
apt-get update -qq
apt-get upgrade -y -qq
apt-get install -y -qq \
    curl wget git unzip software-properties-common \
    apt-transport-https ca-certificates gnupg2 lsb-release \
    fail2ban ufw logrotate cron
log "Systeem bijgewerkt"

##############################################################################
# 2. FIREWALL (UFW)
##############################################################################
info "Stap 2/8: Firewall configureren..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp comment 'SSH'
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'
ufw --force enable
log "UFW firewall actief (22, 80, 443)"

# Fail2ban
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime  = 1h
findtime = 10m
maxretry = 5

[sshd]
enabled = true
port    = ssh
logpath = %(sshd_log)s

[nginx-req-limit]
enabled  = true
filter   = nginx-req-limit
action   = iptables-multiport[name=ReqLimit, port="http,https", protocol=tcp]
logpath  = /var/log/nginx/error.log
findtime = 1m
maxretry = 10
EOF
systemctl enable fail2ban
systemctl start fail2ban
log "Fail2ban geconfigureerd"

##############################################################################
# 3. NGINX + PHP 8.2 + MySQL 8.0
##############################################################################
info "Stap 3/8: LEMP stack installeren..."

# Nginx
apt-get install -y -qq nginx
systemctl enable nginx

# PHP 8.2
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq \
    php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-gd \
    php8.2-mbstring php8.2-zip php8.2-bcmath php8.2-intl php8.2-imagick

# PHP configuratie aanpassen
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 64M/' /etc/php/8.2/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 64M/' /etc/php/8.2/fpm/php.ini
sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.2/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 120/' /etc/php/8.2/fpm/php.ini
systemctl restart php8.2-fpm

# MySQL 8.0
apt-get install -y -qq mysql-server

# MySQL root configureren (zonder interactief)
mysql --execute="ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASS}';" 2>/dev/null || true
mysql -u root -p"${DB_ROOT_PASS}" --execute="DELETE FROM mysql.user WHERE User='';" 2>/dev/null || true
mysql -u root -p"${DB_ROOT_PASS}" --execute="DROP DATABASE IF EXISTS test;" 2>/dev/null || true
mysql -u root -p"${DB_ROOT_PASS}" --execute="FLUSH PRIVILEGES;" 2>/dev/null || true

log "LEMP stack geïnstalleerd (Nginx + PHP 8.2 + MySQL 8.0)"

##############################################################################
# 4. DATABASES AANMAKEN
##############################################################################
info "Stap 4/8: Databases aanmaken..."

mysql -u root -p"${DB_ROOT_PASS}" << SQL
CREATE DATABASE IF NOT EXISTS ${DB_WP_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_WP_USER}'@'localhost' IDENTIFIED BY '${DB_WP_PASS}';
GRANT ALL PRIVILEGES ON ${DB_WP_NAME}.* TO '${DB_WP_USER}'@'localhost';

CREATE DATABASE IF NOT EXISTS ${DB_LS_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_LS_USER}'@'localhost' IDENTIFIED BY '${DB_LS_PASS}';
GRANT ALL PRIVILEGES ON ${DB_LS_NAME}.* TO '${DB_LS_USER}'@'localhost';

FLUSH PRIVILEGES;
SQL

log "Databases aangemaakt"

##############################################################################
# 5. WORDPRESS INSTALLEREN
##############################################################################
info "Stap 5/8: WordPress installeren..."

# WP-CLI
curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp

# WordPress downloaden
mkdir -p "$WP_DIR"
cd "$WP_DIR"
wp core download --locale=nl_NL --allow-root
wp config create \
    --dbname="$DB_WP_NAME" \
    --dbuser="$DB_WP_USER" \
    --dbpass="$DB_WP_PASS" \
    --locale=nl_NL \
    --allow-root

wp core install \
    --url="https://$WP_DOMAIN" \
    --title="CleanMasterzz" \
    --admin_user="$WP_ADMIN_USER" \
    --admin_password="$WP_ADMIN_PASS" \
    --admin_email="$WP_ADMIN_EMAIL" \
    --allow-root

# Extra wp-config instellingen
wp config set WP_DEBUG false --raw --allow-root
wp config set WP_MEMORY_LIMIT "256M" --allow-root
wp config set DISALLOW_FILE_EDIT true --raw --allow-root

# Basis permalink instelling
wp option update permalink_structure '/%postname%/' --allow-root
wp rewrite flush --allow-root

# Rechten instellen
chown -R www-data:www-data "$WP_DIR"
find "$WP_DIR" -type d -exec chmod 755 {} \;
find "$WP_DIR" -type f -exec chmod 644 {} \;

log "WordPress geïnstalleerd op $WP_DOMAIN"

##############################################################################
# 6. LICENSE SERVER DEPLOYEN
##############################################################################
info "Stap 6/8: License Server deployen..."

mkdir -p "$LS_DIR/api" "$LS_DIR/admin"

# Kopieer bestanden (vanuit de plugin repo als die beschikbaar is, anders placeholder)
if [ -d "/tmp/cleanmasterzz/license-server" ]; then
    cp -r /tmp/cleanmasterzz/license-server/* "$LS_DIR/"
    log "License server bestanden gekopieerd"
else
    warn "License server bestanden niet gevonden in /tmp/cleanmasterzz/license-server"
    warn "Upload handmatig via: scp -r license-server/* root@server:$LS_DIR/"
fi

# Rechten
chown -R www-data:www-data "$LS_DIR"
chmod 640 "$LS_DIR/config.php" 2>/dev/null || true

log "License server map aangemaakt: $LS_DIR"

##############################################################################
# 7. NGINX CONFIGURATIE
##############################################################################
info "Stap 7/8: Nginx configureren..."

# WordPress virtual host
cat > /etc/nginx/sites-available/$WP_DOMAIN << NGINX_WP
server {
    listen 80;
    server_name $WP_DOMAIN www.$WP_DOMAIN;
    root $WP_DIR;
    index index.php;

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options SAMEORIGIN;
    add_header X-XSS-Protection "1; mode=block";

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, no-transform";
    }

    location ~ /\. { deny all; }
    location ~* /(?:uploads|files)/.*\.php$ { deny all; }

    access_log /var/log/nginx/${WP_DOMAIN}.access.log;
    error_log  /var/log/nginx/${WP_DOMAIN}.error.log;
}
NGINX_WP

# License server virtual host
cat > /etc/nginx/sites-available/$LS_DOMAIN << NGINX_LS
server {
    listen 80;
    server_name $LS_DOMAIN;
    root $LS_DIR;
    index index.php;

    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;

    location /api/ {
        try_files \$uri \$uri/ /api/index.php?\$query_string;
    }

    location /admin/ {
        try_files \$uri \$uri/ /admin/index.php?\$query_string;
    }

    location ~ /config\.php { deny all; return 404; }
    location ~ /install\.php { deny all; return 404; }
    location ~ /\. { deny all; return 404; }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    access_log /var/log/nginx/${LS_DOMAIN}.access.log;
    error_log  /var/log/nginx/${LS_DOMAIN}.error.log;
}
NGINX_LS

ln -sf /etc/nginx/sites-available/$WP_DOMAIN /etc/nginx/sites-enabled/
ln -sf /etc/nginx/sites-available/$LS_DOMAIN /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

nginx -t && systemctl reload nginx
log "Nginx geconfigureerd"

##############################################################################
# 8. SSL (LET'S ENCRYPT)
##############################################################################
info "Stap 8/8: SSL certificaten installeren..."

apt-get install -y -qq certbot python3-certbot-nginx

certbot --nginx \
    -d "$WP_DOMAIN" -d "www.$WP_DOMAIN" \
    --non-interactive \
    --agree-tos \
    --email "$WP_ADMIN_EMAIL" \
    --redirect || warn "SSL voor WordPress mislukt — controleer DNS"

certbot --nginx \
    -d "$LS_DOMAIN" \
    --non-interactive \
    --agree-tos \
    --email "$WP_ADMIN_EMAIL" \
    --redirect || warn "SSL voor License Server mislukt — controleer DNS"

# Auto-vernieuwing
echo "0 3 * * * root certbot renew --quiet --post-hook 'systemctl reload nginx'" > /etc/cron.d/certbot-renew
log "SSL certificaten geïnstalleerd"

##############################################################################
# AUTOMATISCHE DAGELIJKSE BACKUP
##############################################################################
cat > /usr/local/bin/cmcalc-backup.sh << 'BACKUP'
#!/bin/bash
BACKUP_DIR="/var/backups/cleanmasterzz"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p "$BACKUP_DIR"

# WordPress database
mysqldump -u wp_user -p"DB_WP_PASS_PLACEHOLDER" wordpress | gzip > "$BACKUP_DIR/wp_db_$DATE.sql.gz"

# License server database
mysqldump -u cmcalc_user -p"DB_LS_PASS_PLACEHOLDER" cmcalc_licenses | gzip > "$BACKUP_DIR/ls_db_$DATE.sql.gz"

# WordPress bestanden
tar -czf "$BACKUP_DIR/wp_files_$DATE.tar.gz" /var/www/html/wp-content/

# Verwijder backups ouder dan 14 dagen
find "$BACKUP_DIR" -name "*.gz" -mtime +14 -delete

echo "[$(date)] Backup voltooid" >> /var/log/cmcalc-backup.log
BACKUP

# Vul DB wachtwoorden in
sed -i "s/DB_WP_PASS_PLACEHOLDER/$DB_WP_PASS/" /usr/local/bin/cmcalc-backup.sh
sed -i "s/DB_LS_PASS_PLACEHOLDER/$DB_LS_PASS/" /usr/local/bin/cmcalc-backup.sh
chmod +x /usr/local/bin/cmcalc-backup.sh

echo "0 2 * * * root /usr/local/bin/cmcalc-backup.sh" > /etc/cron.d/cmcalc-backup
log "Automatische backups geconfigureerd (dagelijks 02:00)"

##############################################################################
# ONATTENDED UPGRADES (AUTOMATISCHE BEVEILIGINGSUPDATES)
##############################################################################
apt-get install -y -qq unattended-upgrades
dpkg-reconfigure -plow unattended-upgrades <<< $'Y'
log "Automatische beveiligingsupdates ingeschakeld"

##############################################################################
# SAMENVATTING
##############################################################################
echo ""
echo -e "${GREEN}══════════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}    CLEANMASTERZZ VPS INSTALLATIE VOLTOOID!${NC}"
echo -e "${GREEN}══════════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}── WordPress ─────────────────────────────────────────────────${NC}"
echo "   URL:             https://$WP_DOMAIN"
echo "   Admin URL:       https://$WP_DOMAIN/wp-admin/"
echo "   Admin gebruiker: $WP_ADMIN_USER"
echo "   Admin wachtwoord: $WP_ADMIN_PASS"
echo ""
echo -e "${BLUE}── License Server ────────────────────────────────────────────${NC}"
echo "   URL:     https://$LS_DOMAIN"
echo "   Admin:   https://$LS_DOMAIN/admin/"
echo "   UPLOAD LICENSE SERVER BESTANDEN naar: $LS_DIR/"
echo "   Daarna:  https://$LS_DOMAIN/install.php?token=INSTALL_TOKEN"
echo ""
echo -e "${BLUE}── Database ──────────────────────────────────────────────────${NC}"
echo "   MySQL root wachtwoord:  $DB_ROOT_PASS"
echo "   WordPress DB:  $DB_WP_NAME / $DB_WP_USER / $DB_WP_PASS"
echo "   License DB:    $DB_LS_NAME / $DB_LS_USER / $DB_LS_PASS"
echo ""
echo -e "${YELLOW}⚠️  BEWAAR BOVENSTAANDE GEGEVENS OP EEN VEILIGE PLEK!${NC}"
echo ""
echo -e "${BLUE}── Volgende stappen ───────────────────────────────────────────${NC}"
echo "   1. Upload license-server bestanden naar $LS_DIR/"
echo "   2. Pas $LS_DIR/config.php aan met DB gegevens"
echo "   3. Voer install.php uit om database tabellen aan te maken"
echo "   4. Verwijder install.php"
echo "   5. Installeer Cleanmasterzz plugin in WordPress"
echo "   6. Stel LICENSE SERVER URL in: $LS_DOMAIN"
echo ""
# Sla gegevens op in een bestand (alleen root leesbaar)
cat > /root/installation-credentials.txt << CREDS
CLEANMASTERZZ VPS INSTALLATIE GEGEVENS
Datum: $(date)

WordPress:
  URL: https://$WP_DOMAIN
  Admin: $WP_ADMIN_USER / $WP_ADMIN_PASS
  Database: $DB_WP_NAME / $DB_WP_USER / $DB_WP_PASS

License Server:
  URL: https://$LS_DOMAIN
  Database: $DB_LS_NAME / $DB_LS_USER / $DB_LS_PASS

MySQL Root: $DB_ROOT_PASS
CREDS
chmod 600 /root/installation-credentials.txt
log "Gegevens opgeslagen in /root/installation-credentials.txt"
