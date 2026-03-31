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
