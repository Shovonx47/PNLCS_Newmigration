## Quick Start with Docker

The fastest way to try PNLCS is the official Docker image `panelica/pnlcs-runtime`. It bundles PHP-FPM 8.4 + nginx + supervisor, and on its first start installs the newest stable release: a signed package, verified before it is unpacked, with its PHP dependencies and frontend already built.

```bash
docker network create pnlcs-net

docker run -d --name pnlcs-db --network pnlcs-net \
  -v pnlcs_db:/var/lib/mysql \
  -e MYSQL_ROOT_PASSWORD=choose-a-root-password \
  -e MYSQL_DATABASE=pnlcs -e MYSQL_USER=pnlcs -e MYSQL_PASSWORD=choose-a-password \
  mariadb:11

docker run -d --name pnlcs --network pnlcs-net -p 8090:80 \
  -v pnlcs_app:/var/www/pnlcs \
  -e DB_HOST=pnlcs-db -e DB_DATABASE=pnlcs \
  -e DB_USERNAME=pnlcs -e DB_PASSWORD=choose-a-password \
  -e APP_URL=http://localhost:8090 \
  panelica/pnlcs-runtime:1.5
```

The two named volumes keep your database and your installation (with its `.env` and uploaded files) when the container is replaced. The first start takes about a minute; then visit `http://localhost:8090/install` to run the in-app install wizard. It checks the requirements, creates the administrator with the username and password you choose, and closes itself for good. The container runs the scheduler and a queue worker itself: there is no cron line to add.

To update to the newest release later (your changes are kept, and an update that is interrupted rolls itself back):

```bash
docker exec pnlcs /usr/local/bin/update.sh
```

📦 Full image documentation, environment variables, screenshots, and production deployment notes: 👉 https://hub.docker.com/r/panelica/pnlcs-runtime

---

## Self-Hosted Installation (without Docker)

### One command

On a fresh Ubuntu 22.04/24.04, Debian 12/13, AlmaLinux 9 or Rocky Linux 9 server:

```bash
curl -fsSL https://raw.githubusercontent.com/Panelica/pnlcs/main/install.sh | sudo bash -s -- --domain billing.example.com
```

It installs nginx, PHP 8.4 and MySQL or MariaDB, downloads the newest release and verifies its signature, creates the database, and sets up nginx, the scheduler, the firewall and SELinux. Then it prints the address of the install wizard, where you create your administrator account. It never touches an existing installation. Options and details: `docs/install/native.md`.

### Upload the release package (no command line)

On shared hosting, or any panel with a file manager: download `pnlcs-X.Y.Z.tar.gz` from the latest release, extract it next to the webroot, point the domain's document root at `pnlcs/public` and open the site. The install wizard starts by itself: it writes `.env`, asks for the database, creates the tables, then your administrator account. Details: `docs/install/hosting-panel.md`.

### Step by step

This is a plain Laravel install on your own server: PHP-FPM, MySQL or MariaDB, and Nginx. Every command below was run, in this order, on freshly created servers:

| Operating system | PHP | Database | Result |
|---|---|---|---|
| Ubuntu 24.04.5 LTS | 8.4.25 (ondrej PPA) | MySQL 8.0.46 | ✅ installed, wizard completed, scheduler and update tested |
| Debian 13 (trixie) | 8.4.24 (Debian) | MariaDB 11.8.6 | ✅ installed, wizard completed, scheduler and update tested |
| AlmaLinux 9.8 | 8.4.26 (Remi) | MySQL 8.0.46 (AppStream) | ✅ installed with SELinux enforcing (0 denials), wizard completed, scheduler and update tested |

Rocky Linux 9 uses the same commands as AlmaLinux 9.

**The one rule that prevents most install problems:** the whole PNLCS directory belongs to the web server user, and every `composer`, `php artisan` and `npm` command runs as that user. The install wizard writes `.env`, the application writes `storage/`, and updates run git, composer and npm inside the tree. When the tree is owned by root you get a 500 on the last step of the wizard (`.env` not writable), `fatal: detected dubious ownership` from `git pull`, and `EACCES` from npm — we reproduced all three by following the old instructions on a clean Ubuntu server.

The values that differ between the two families, used throughout this guide:

| | Ubuntu / Debian | AlmaLinux / Rocky |
|---|---|---|
| Web server user | `www-data` | `apache` (PHP-FPM runs as `apache`, Nginx can reach its socket) |
| PHP-FPM socket | `/run/php/php8.4-fpm.sock` | `/run/php-fpm/www.sock` |
| PHP-FPM service | `php8.4-fpm` | `php-fpm` |
| Database service | `mysql` (Ubuntu) / `mariadb` (Debian) | `mysqld` |
| Nginx site file | `/etc/nginx/sites-available/pnlcs` | `/etc/nginx/conf.d/pnlcs.conf` |
| SELinux | not used | enforcing — step 10 is required |

The guide uses `/var/www/pnlcs` as the install directory and `example.com` as the address — replace both with your own.

---

### 0. Prepare the server

Skip this if PHP 8.4, a database, Node and Composer are already installed (a control panel such as Panelica gives you all of them — see *Installing inside a hosting-panel account*).

#### Ubuntu 24.04

Ubuntu 24.04 ships PHP 8.3, which is not enough, so PHP 8.4 comes from the ondrej PPA.

```bash
sudo apt update
sudo apt install -y software-properties-common git unzip curl cron
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
  php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl php8.4-imap
sudo apt install -y mysql-server nginx

# Node.js 20 LTS (only for building the frontend assets)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### Debian 13

Debian 13 carries PHP 8.4 itself — no extra repository. Two differences from Ubuntu, both found on a clean Debian 13 server:

- There is no `php8.4-imap` package. Leave it out: if you put it in the list, apt refuses the whole line and installs no PHP at all. `imap` is optional (mailbox → ticket import only).
- There is no `mysql-server` package. Debian ships MariaDB, which PNLCS supports.
- `cron` is not installed on the Debian 13 cloud image — without it the scheduler in step 13 never runs. It is in the list below.

```bash
sudo apt update
sudo apt install -y git unzip curl cron
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring \
  php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl
sudo apt install -y mariadb-server nginx

curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### AlmaLinux 9 / Rocky Linux 9

PHP 8.4 comes from the Remi repository. The zip extension is a separate package here (`php-pecl-zip`), and unlike Debian-based systems the services are not started for you.

```bash
sudo dnf install -y epel-release https://rpms.remirepo.net/enterprise/remi-release-9.rpm
sudo dnf module reset php -y
sudo dnf module enable php:remi-8.4 -y
sudo dnf install -y php php-fpm php-mysqlnd php-mbstring php-xml php-gd \
  php-bcmath php-intl php-imap php-pecl-zip \
  mysql-server nginx git unzip cronie policycoreutils-python-utils

curl -fsSL https://rpm.nodesource.com/setup_20.x | sudo bash -
sudo dnf install -y nodejs

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Start the services now and on every boot.
sudo systemctl enable --now php-fpm mysqld nginx
```

#### Check what you have

```bash
php -v          # PHP 8.4.x — 8.3 will 500 at runtime
php -m | grep -E '^(bcmath|curl|dom|fileinfo|gd|intl|mbstring|openssl|pdo_mysql|tokenizer|xml|zip)$' | wc -l   # 12
mysql --version # MySQL 8.0 / MariaDB 10.6 or newer
node -v         # v20.19 or newer
composer -V     # Composer version 2.x
```

**Firewall.** The cloud images we tested had no firewall enabled. If yours does, open HTTP and HTTPS — `sudo ufw allow 'Nginx Full'` on Ubuntu, or `sudo firewall-cmd --permanent --add-service=http --add-service=https && sudo firewall-cmd --reload` on AlmaLinux/Rocky.

---

### 1. Get the code and hand it to the web server user

```bash
sudo git clone https://github.com/Panelica/pnlcs.git /var/www/pnlcs
sudo chown -R www-data:www-data /var/www/pnlcs     # AlmaLinux/Rocky: apache:apache
cd /var/www/pnlcs
```

Define a short helper for the rest of this guide. It runs a command as the web server user, with a writable home for the Composer and npm caches (the web user's own home, `/var/www`, belongs to root):

```bash
# Ubuntu / Debian
pn() { sudo -u www-data HOME=/tmp/pnlcs-home COMPOSER_HOME=/tmp/pnlcs-home/composer "$@"; }

# AlmaLinux / Rocky
pn() { sudo -u apache HOME=/tmp/pnlcs-home COMPOSER_HOME=/tmp/pnlcs-home/composer "$@"; }
```

The helper only lives in your current shell — define it again if you log in later.

### 2. Install PHP dependencies

```bash
pn composer install --no-dev --optimize-autoloader --no-interaction
```

**AlmaLinux/Rocky:** `sudo` there does not search `/usr/local/bin`, so the line above answers `sudo: composer: command not found`. Use the full path:

```bash
pn /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction
```

### 3. Create the database

Open the database console as root — on a fresh server root signs in through the system account, so there is no password to type:

```bash
sudo mysql
```

Then create the database and a user for PNLCS (MySQL and MariaDB accept the same statements):

```sql
CREATE DATABASE pnlcs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pnlcs'@'localhost' IDENTIFIED BY 'choose-a-strong-password';
GRANT ALL PRIVILEGES ON pnlcs.* TO 'pnlcs'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Configure the environment

```bash
pn cp .env.example .env
sudo nano .env
```

Set at least these values (editing the existing file keeps its owner):

```env
APP_NAME="Your Company"
APP_URL=https://example.com
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pnlcs
DB_USERNAME=pnlcs
DB_PASSWORD=choose-a-strong-password

MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="Your Company"
```

Then make `.env` readable by the web user only — it holds your database password and application key, and the install wizard must be able to write it:

```bash
sudo chown www-data:www-data .env     # AlmaLinux/Rocky: apache:apache
sudo chmod 640 .env
```

**Notes**

- Keep `DB_CONNECTION=mysql` for MariaDB too; do not switch to sqlite (some migrations use MySQL-specific SQL).
- If the site starts on plain HTTP while you set up TLS, use `http://` in `APP_URL` for now and change it in step 15.
- Mail server settings (SMTP host, user, password) are entered in the admin panel later, not here.

### 5. Generate the application key

```bash
pn php artisan key:generate
```

### 6. Run the database migrations

```bash
pn php artisan migrate --force
```

Do **not** run `php artisan db:seed` here. The install wizard you will open in step 12 runs the seeder itself and renames the seeded administrator to the username and password you choose. Seeding by hand creates an administrator first - and the wizard, seeing one, locks itself before you ever reach it, leaving you with an admin account whose password only the seeder's output shows.

The wizard's seeding provides everything an installation starts with: four starter currencies (USD, EUR, GBP, TRY), ticket departments and statuses, 33 email templates, 30 languages, the full translation set, the knowledge base, and default homepage sections.

**Headless installs only:** if you are scripting an installation with no browser step at all, `php artisan db:seed --force` is how you seed - the administrator is then `admin`, with a random password the seeder prints once (set `PNLCS_ADMIN_PASSWORD` beforehand to choose it). The wizard stays closed by design, and signing in to change that password is your first job.

### 7. Build the frontend assets

```bash
pn npm ci
pn npm run build
```

### 8. Link public storage

```bash
pn php artisan storage:link
```

### 9. Cache configuration, routes and views

```bash
pn php artisan optimize
```

### 10. SELinux (AlmaLinux / Rocky only)

SELinux is enforcing on AlmaLinux and Rocky. Without these lines every page answers 500: PHP-FPM may not write to `storage/`, and may not connect to the database. We measured exactly those two denials in the audit log of a clean AlmaLinux 9 server.

```bash
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/pnlcs/storage(/.*)?"
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/pnlcs/bootstrap/cache(/.*)?"
sudo semanage fcontext -a -t httpd_sys_rw_content_t "/var/www/pnlcs/\.env"
sudo restorecon -R /var/www/pnlcs

sudo setsebool -P httpd_can_network_connect_db 1   # PHP → MySQL/MariaDB
sudo setsebool -P httpd_can_network_connect 1      # PHP → payment gateways, server and registrar APIs, SMTP
```

Do not switch SELinux off to make the errors go away — these rules give the application exactly what it needs and nothing more.

### 11. Point Nginx at `public/`

The document root must be the `public/` directory, never the project root — pointing it at the project root exposes `.env`.

**Ubuntu / Debian** — create `/etc/nginx/sites-available/pnlcs`:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/pnlcs/public;      # note: /public

    index index.php;
    charset utf-8;
    client_max_body_size 64M;        # ticket + backup uploads

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Enable it and remove the default site — otherwise a request to the server's IP address keeps landing on the "Welcome to nginx" page:

```bash
sudo ln -s /etc/nginx/sites-available/pnlcs /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

**AlmaLinux / Rocky** — create `/etc/nginx/conf.d/pnlcs.conf` with the same block, changing only the PHP-FPM socket:

```nginx
        fastcgi_pass unix:/run/php-fpm/www.sock;
```

```bash
sudo nginx -t && sudo systemctl reload nginx
```

Check it before you open a browser:

```bash
curl -sI http://example.com/install | head -1      # HTTP/1.1 302 Found  (→ the wizard)
curl -sI http://example.com/.env | head -1         # 403 or 404 — never 200
```

Using Apache or Caddy instead? The same rule applies: document root = `public/`, PHP handled by PHP-FPM 8.4, and every request that is not a file rewritten to `index.php`.

### 12. Run the install wizard

Open `http://example.com/install` in your browser. The wizard walks through:

1. **Requirements** — PHP version, every required extension, and whether `storage/`, `bootstrap/cache/` and `.env` are writable. Everything must be green before *Continue* appears; a red line names exactly what to fix.
2. **Database** — skipped automatically, because you already ran the migrations in step 6.
3. **Administrator** — the username, email and password you choose. There is no default password to change afterwards.
4. **Application** — the public URL, the company name and the default language.
5. **Finish** — the wizard writes a lock file and closes itself permanently; `/install` answers 404 from then on.

Sign in at `http://example.com/admin/login`.

### 13. Schedule the cron runner

Everything that happens by itself — invoice generation, payment reminders, suspensions, SSL polling, backups, queued mail — is driven by one cron line for the web server user:

```bash
sudo crontab -u www-data -e     # AlmaLinux/Rocky: -u apache
```

```cron
* * * * * cd /var/www/pnlcs && php artisan schedule:run >> /dev/null 2>&1
```

Check that the schedule is there and runs cleanly:

```bash
pn php artisan schedule:list
pn php artisan schedule:run
```

### 14. Queue: no separate worker needed

`.env.example` ships `QUEUE_CONNECTION=sync`: mail and background jobs run inline, which is the right shape for a single server.

If you switch to `QUEUE_CONNECTION=database`, the cron line from step 13 already processes the queue: the scheduler starts a worker every minute that drains the queue and stops. We verified this on a clean server — a queued email was in the `jobs` table before `schedule:run`, and sent after it, with no supervisor installed. Jobs therefore wait up to a minute.

Add a permanent worker only if you want queued jobs to run instantly. A supervisor entry for that:

```ini
[program:pnlcs-worker]
command=php /var/www/pnlcs/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
```

### 15. Turn on HTTPS

Checkout and the admin login must never run over plain HTTP. With Certbot (Ubuntu/Debian: `sudo apt install -y certbot python3-certbot-nginx`):

```bash
sudo certbot --nginx -d example.com
```

Then make sure `APP_URL` in `.env` starts with `https://` and refresh the cache:

```bash
pn php artisan optimize
```

### 16. Final check

```bash
curl -sI https://example.com/install | head -1   # 404 — the wizard is closed
curl -sI https://example.com/.env | head -1      # 403 or 404
ls -l /var/www/pnlcs/.env                        # owned by the web user, mode 640
pn php artisan schedule:run                      # finishes without errors
```

Then continue with *First Steps After Installation*.

---

## Updating

PNLCS is updated from signed releases, never from the code under development. An update keeps everything that belongs to your installation — `.env`, `storage/`, the database's data, your own theme, modules and hook files, the email templates and translations you edited. It stops and asks when one of your changes clashes with the new version, and puts every file and the database back by itself if anything goes wrong.

There are two channels, chosen on **Setup → Updates**: **Stable** (the default) and **Beta**, which gets new releases about a week earlier. PNLCS looks for a newer release once a day and tells you; nothing is installed until an administrator asks for it.

### From the admin area

1. **Setup → Updates → Check this update.** The release is downloaded, its signature is verified, and it is compared with your installation. Nothing is changed yet: you see what will be updated and which of your changes are merged or kept.
2. If you changed a file the new version changes in the same place, choose for each one: the new version, yours, or the merged text.
3. **Update now.** The site goes into maintenance for the few seconds it takes, a snapshot of the database is taken, the files are written, the migrations run and the new version is checked. If any step fails, everything is put back and the site returns on the version it had.

### From the command line

Run as the web server user (the owner of the PNLCS files):

```bash
php artisan pnlcs:update --check     # compare, change nothing
php artisan pnlcs:update             # update to the newest release on your channel
php artisan pnlcs:update-rollback    # put back an update that was cut off
```

### Docker

```bash
docker exec pnlcs /usr/local/bin/update.sh            # update
docker exec pnlcs /usr/local/bin/update.sh --check    # compare only
```

`update.sh` runs the same updater, then reloads PHP. Your database and uploaded files live on the volumes and are left untouched. Set `AUTO_UPDATE=1` on the container to apply the newest release on every start.

It updates PNLCS, not the image: nginx, PHP and the entrypoint come from the image, and a fix to any of them arrives by pulling the tag again and recreating the container with the same volumes. Images before 1.5 reset the code to the development branch on every update and discarded changes made inside the container; pull `panelica/pnlcs-runtime:1.5` and recreate the container before you update.

### Installations cloned with git

An installation cloned with git is compared with the commit it was cloned at, so your changes are kept just the same, and from its first update on it is a release installation. Keep using the updater, not `git pull`: `main` is the code under development, not a release.

Conflicts, cut-off updates, ZIP installations and bringing an installation older than 1.3 up to date by hand are covered in `docs/install/updating.md`.

---

## First Steps After Installation

📖 New to hosting billing? The full user guide walks you through every concept and task in plain language — start with *Your First Sale* for an end-to-end walkthrough. The steps below are the quick version.

Once the site loads and you can reach `/admin/login`, do these in order:

### 1. Sign in

- URL: `https://example.com/admin/login`
- Use the administrator username and password you chose in the install wizard. (Only a headless install that seeded by hand has the account `admin`, with the password `db:seed` printed - if that is you, changing it is the first job.)
- (Recommended) Enable Two-Factor Authentication from **My Account** (the menu under your name) → **Turn on**.

### 2. Configure General Settings

**Setup → General Settings**

- Company name, support email, logo, favicon
- Default language, currency, timezone
- Date format, invoice pay terms, tax behavior

### 3. Configure Email Delivery

**Setup → General Settings → Mail Configuration**

- Choose SMTP and fill in the host, port, username, password and encryption of your mail server or relay
- Set the sender address and name
- Save, then press **Send Test Email** and check the inbox

These settings are stored in the database and override the `MAIL_*` values in `.env`. Until mail is configured, `.env.example`'s `MAIL_MAILER=log` is in effect: nothing is delivered, and with the default `LOG_LEVEL=warning` the messages are not written to the log either.

### 4. Customize Appearance

**Setup → Appearance**

- Pick a theme from the 16 built-in options
- Upload your logo and favicon
- Configure homepage sections (hero, features, pricing, testimonials)
- Set up white-label footer text

### 5. Add Payment Gateways

**Setup → Payment Gateways**

- **Stripe** — paste your API keys, enable
- **PayPal** — client ID + secret (sandbox or live)
- **Bank Transfer** — set instructions shown to clients
- Test each gateway with a small order before going live

### 6. Add Server Modules (if you sell hosting)

**Setup → Servers**

- Add your server: Panelica, cPanel, Plesk, DirectAdmin, HestiaCP, aaPanel, Proxmox VE or Vultr
- Test the API connection from the server edit page
- Assign servers to server groups if you have multiple

### 7. Create Your First Product

**Setup → Products/Services**

- Create a product group (e.g. "Shared Hosting")
- Create a product, link it to a server and a module
- Set pricing for monthly / quarterly / annually
- Enable auto-setup if you want provisioning on payment

### 8. Configure Domain Pricing (if you sell domains)

**Setup → Domain Pricing**

- Add TLDs you sell (.com, .net, ...)
- Set registration, transfer, and renewal prices
- Link to a registrar module (or use Manual)

### 9. Set Up Tax Rules

**Setup → Tax Rules**

- Add rates per country, or per state within a country, and a global default
- Rates are added on top of prices; a client marked tax exempt pays none
- See the Tax Rules guide

### 10. (Optional) Invite Staff and Define Roles

**Setup → Admin Roles** and **Setup → Admin Accounts**

- Create custom roles (e.g. "Billing Manager", "Support Agent")
- Pick permissions per role from the 45+ available
- Add staff members and assign roles

### 11. Enable Email Verification for Signups (recommended)

**Setup → General Settings → Email Verification**

- Tick **New customers must confirm their email address**
- New signups will receive a verification link before they can order

### 12. Test the Full Flow End-to-End

1. Open an incognito browser
2. Visit `/client/register` and create a test client
3. Place a test order for one of your products
4. Pay with the test mode of your gateway
5. Verify the invoice, service, and email flow all work
