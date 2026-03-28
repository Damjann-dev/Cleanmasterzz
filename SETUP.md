# 🔧 Interne Setup Documentatie — Cleanmasterzz

> **INTERN GEBRUIK — niet delen met klanten**

---

## 🖥️ Licentieserver deployment (VPS)

### Vereisten
- Nginx + PHP 7.4+ + MySQL 5.7+
- VPS: `185.228.82.252`
- Domain: `cleanmasterzz.nl`

### Stap 1 — Bestanden deployen

```bash
cd /tmp && git clone https://TOKEN@github.com/Damjann-dev/Cleanmasterzz cleanmasterzz-tmp
cd cleanmasterzz-tmp && git checkout claude/code-review-analysis-ilS6A
cp -r license-server/* /var/www/licenses/
cp licenses-api.php /var/www/html/licenses-api.php
```

### Stap 2 — Database aanmaken

```bash
DBPASS=$(grep -m1 'password' /etc/mysql/debian.cnf | awk '{print $3}')
mysql -u debian-sys-maint -p"$DBPASS" -e "
  CREATE DATABASE IF NOT EXISTS cleanmasterzz_licenses;
  CREATE USER IF NOT EXISTS 'cmcalc_user'@'localhost' IDENTIFIED BY 'WACHTWOORD';
  GRANT ALL ON cleanmasterzz_licenses.* TO 'cmcalc_user'@'localhost';
  FLUSH PRIVILEGES;
"
```

### Stap 3 — Config schrijven

```bash
python3 << 'PYEOF'
config = """<?php
define( 'DB_HOST',     'localhost' );
define( 'DB_NAME',     'cleanmasterzz_licenses' );
define( 'DB_USER',     'cmcalc_user' );
define( 'DB_PASS',     'WACHTWOORD' );
define( 'DB_CHARSET',  'utf8mb4' );
define( 'ADMIN_PASSWORD_HASH', 'BCRYPT_HASH' );
define( 'ADMIN_USERNAME',      'admin' );
define( 'SECRET_KEY',          'LANGE_RANDOM_STRING' );
define( 'SERVER_VERSION',      '1.0.0' );
date_default_timezone_set( 'Europe/Amsterdam' );
define( 'DEBUG_MODE', false );
define( 'SESSION_NAME', 'cmcalc_ls_session' );
define( 'ADMIN_BASE',   '/licenses/admin' );
"""
open('/var/www/licenses/config.php', 'w').write(config)
print("Config geschreven")
PYEOF
```

Genereer bcrypt hash voor admin wachtwoord:
```bash
php -r 'echo password_hash("JOUWWACHTWOORD", PASSWORD_BCRYPT);'
```

### Stap 4 — Tabellen aanmaken

Bezoek eenmalig: `http://185.228.82.252/licenses/install.php`
Daarna verwijderen: `rm /var/www/licenses/install.php`

---

## ⚙️ Nginx configuratie

```nginx
location ^~ /licenses/api/ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME /var/www/licenses/api/index.php;
}
location ^~ /licenses/admin/ {
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME /var/www/licenses/admin/index.php;
}
```

---

## 🔑 Admin panel

- URL: `http://185.228.82.252/licenses/admin/`
- Gebruiker: `admin`
- Wachtwoord: zie config op de server

---

## 🔄 Plugin updaten op de server

```bash
cd /tmp/cleanmasterzz-tmp && git pull
cp includes/class-cmcalc-license.php /var/www/html/wp-content/plugins/cleanmasterzz-calculator/includes/
cp -r admin/views/ /var/www/html/wp-content/plugins/cleanmasterzz-calculator/admin/views/
```

---

## 🗃️ Database info

| | |
|---|---|
| WP database | `cleanmasterzz_wp` |
| Licentie database | `cleanmasterzz_licenses` |
| DB gebruiker | `cmcalc_user` |
| MySQL root toegang | `debian-sys-maint` via `/etc/mysql/debian.cnf` |

---

## 📦 Server info

| | |
|---|---|
| IP | `185.228.82.252` |
| WordPress root | `/var/www/html/` |
| Plugin map | `/var/www/html/wp-content/plugins/cleanmasterzz-calculator/` |
| Licentieserver | `/var/www/licenses/` |
| Nginx config | `/etc/nginx/sites-available/cleanmasterzz` |
| PHP versie | 7.4 |
