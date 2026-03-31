# COINGROW

COINGROW is a Laravel 12 savings and digital banking application built around secure account management, split-deposit automation, locked savings wallets, and a complete transaction audit trail.

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
- Transaction-safe deposits, withdrawals, lock/unlock actions, and split configuration updates
- Full transaction history with filters and pagination
- Demo seeder with a realistic sample customer journey

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

7. Build assets and run the app:

```bash
npm run build
php artisan serve
```

## Demo account

- Username: `demo`
- Password: `Password123`

## Business rules

- Payment split totals must remain at or below `100%`.
- Locked sub-accounts cannot be withdrawn from.
- Locked sub-accounts auto-unlock when their target amount is reached.
- Sub-accounts can only be deleted when their balance is zero.
- All financial mutations are wrapped in database transactions.
