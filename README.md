# COINGROW

COINGROW is a Laravel 12 savings and digital banking application built around secure account management, split-deposit automation, locked savings wallets, financial insights, and a complete transaction audit trail.

## Stack

- PHP 8.2+
- Laravel 12
- MySQL
- Blade + Tailwind CSS
- Alpine.js

## Features

- Username-based registration, login, logout, and password change
- One primary account per user
- Multiple sub-accounts with optional targets and lock state
- Automatic split distribution on main-account deposits
- Transaction-safe deposits, withdrawals, transfers, lock/unlock actions, and split configuration updates
- Categorized transactions with notes and tags
- Full transaction history with filters and pagination
- In-app activity notifications
- Analytics dashboard with income vs expenses, wallet distribution, and savings growth charts
- Auto-savings rules for daily, weekly, and per-deposit automation
- Scheduled transactions for recurring deposits and transfers
- Financial intelligence cards with savings suggestions, spending watch, burn rate, and runway estimation
- Demo seeder with a realistic sample customer journey

## Current scope by phase

### Core banking

- Secure username/password authentication with hashed passwords
- Main account and multiple savings wallets
- Savings targets with lock/unlock rules
- Payment split management and automatic allocation
- Atomic transaction logging for all balance mutations

### Phase 1

- Wallet-to-wallet transfers
- Transaction categories, notes, and tags
- In-app notifications center
- Analytics dashboard and chart visualizations

### Phase 2

- Auto-savings rules
- Scheduled recurring deposits and transfers
- Scheduler command for automation processing

### Phase 3

- Financial insights persistence
- Predictive spending metrics
- Smart savings and split recommendation cards

## Setup

1. Install PHP 8.2+, Composer, Node.js, and MySQL.
2. Create a MySQL database named `coingrow`.
3. Copy `.env.example` to `.env`.
4. Update `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coingrow
DB_USERNAME=root
DB_PASSWORD=
```

5. Install dependencies:

```bash
composer install
npm install
```

6. Generate the key and seed the schema:

```bash
php artisan key:generate
php artisan migrate:fresh --seed
```

7. Build assets:

```bash
npm run build
```

8. Optional: run automation and insights commands manually:

```bash
php artisan coingrow:run-automations
php artisan coingrow:refresh-insights
```

9. Start the application:

```bash
php artisan serve
```

## Server installer

COINGROW now includes a server-friendly installer command and shell wrapper.

### Option 1: Laravel installer command

After uploading the project, configuring your web server, and setting your `.env`, run:

```bash
composer install --no-dev --optimize-autoloader
php artisan coingrow:install --force
```

Optional demo seed:

```bash
php artisan coingrow:install --force --seed-demo
```

What the installer does:

- validates PHP version, required extensions, and writable paths
- checks database connectivity
- generates `APP_KEY` if missing
- creates the `public/storage` symlink
- runs migrations with `--force`
- optionally seeds demo data
- clears and rebuilds Laravel caches
- writes an install receipt to `storage/app/coingrow-install.json`

### Option 2: Linux server wrapper

For Ubuntu or other Linux servers, you can use:

```bash
chmod +x install.sh
./install.sh
```

If `.env` does not exist yet, the script will create it from `.env.example` and stop so you can fill in your production values before rerunning it.

### Option 3: One-click deployment

COINGROW now includes a deployment wrapper that:

- installs Composer dependencies
- builds frontend assets
- runs the COINGROW installer
- fixes writable permissions for `storage/` and `bootstrap/cache/`
- installs the Laravel scheduler cron entry

Usage:

```bash
chmod +x deploy.sh
./deploy.sh
```

Useful deployment flags:

```bash
SEED_DEMO=1 ./deploy.sh
SKIP_ASSETS=1 ./deploy.sh
SKIP_CRON=1 ./deploy.sh
WWW_USER=www-data ./deploy.sh
CRON_USER=www-data ./deploy.sh
```

If you run the script as `root`, it will also `chown` writable Laravel paths to `WWW_USER`.

## Web server configs

Production-ready starter configs are included in the `deploy/` directory.

### Nginx

Template:

- `deploy/nginx/coingrow.conf`

Typical install on Ubuntu:

```bash
sudo cp deploy/nginx/coingrow.conf /etc/nginx/sites-available/coingrow
sudo ln -s /etc/nginx/sites-available/coingrow /etc/nginx/sites-enabled/coingrow
sudo nginx -t
sudo systemctl reload nginx
```

Before enabling it, update:

- `server_name`
- project path in `root`
- PHP-FPM socket path if your server uses a different PHP version

### Apache

Templates:

- `deploy/apache/coingrow-vhost.conf`
- `public/.htaccess`

Typical install on Ubuntu:

```bash
sudo cp deploy/apache/coingrow-vhost.conf /etc/apache2/sites-available/coingrow.conf
sudo a2enmod rewrite
sudo a2ensite coingrow.conf
sudo apachectl configtest
sudo systemctl reload apache2
```

Before enabling it, update:

- `ServerName`
- project path in `DocumentRoot`

Apache uses the Laravel rewrite rules already present in `public/.htaccess`.

### Production notes

- Point your web server document root to `public/`
- Configure HTTPS/TLS before exposing the app publicly
- Configure the scheduler:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

- Build assets on the server or in your CI pipeline:

```bash
npm ci
npm run build
```

- Use a strong `COINGROW_WEBHOOK_SECRET` in `.env`

## Demo account

- Username: `demo`
- Password: `Password123`

## Business rules

- Payment split totals must remain at or below `100%`.
- Locked sub-accounts cannot be withdrawn from.
- Locked sub-accounts cannot transfer funds out.
- Locked sub-accounts auto-unlock when their target amount is reached.
- Sub-accounts can only be deleted when their balance is zero.
- All financial mutations are wrapped in database transactions.

## Scheduler

COINGROW currently schedules:

- `coingrow:run-automations` hourly
- `coingrow:refresh-insights` daily

If you are running Laravel scheduler in production, make sure the standard scheduler cron entry is configured.
