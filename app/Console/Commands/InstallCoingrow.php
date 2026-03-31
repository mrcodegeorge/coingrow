<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallCoingrow extends Command
{
    protected $signature = 'coingrow:install
        {--seed-demo : Seed the demo account and sample activity}
        {--skip-assets : Skip frontend asset reminders}
        {--no-cache : Skip config, route, and view caching}
        {--force : Allow installation to run in production environments}';

    protected $description = 'Prepare COINGROW for a production or server installation.';

    public function handle(): int
    {
        $this->components->info('Starting COINGROW installation...');

        if (! $this->ensureEnvironmentFile()) {
            return self::FAILURE;
        }

        if (! $this->validateEnvironment()) {
            return self::FAILURE;
        }

        if (! $this->validateDatabaseConnection()) {
            return self::FAILURE;
        }

        if (! $this->ensureApplicationKey()) {
            return self::FAILURE;
        }

        $this->callSilently('optimize:clear');
        $this->callSilently('storage:link');

        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            $this->components->error('Database migrations failed.');

            return self::FAILURE;
        }

        if ($this->option('seed-demo')) {
            if ($this->call('db:seed', ['--class' => 'DemoDataSeeder', '--force' => true]) !== self::SUCCESS) {
                $this->components->error('Demo seed failed.');

                return self::FAILURE;
            }
        }

        if (! $this->option('no-cache')) {
            if ($this->callSilently('config:cache') !== self::SUCCESS ||
                $this->callSilently('route:cache') !== self::SUCCESS ||
                $this->callSilently('view:cache') !== self::SUCCESS) {
                $this->components->error('Application caches could not be built.');

                return self::FAILURE;
            }
        }

        $this->writeInstallReceipt();
        $this->printNextSteps();

        return self::SUCCESS;
    }

    protected function ensureEnvironmentFile(): bool
    {
        $envPath = base_path('.env');

        if (File::exists($envPath)) {
            return true;
        }

        File::copy(base_path('.env.example'), $envPath);

        $this->components->warn('A new .env file was created from .env.example.');
        $this->line('Update your production values in .env, then rerun `php artisan coingrow:install`.');

        return false;
    }

    protected function validateEnvironment(): bool
    {
        $requirementsMet = true;

        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $this->components->error('PHP 8.2 or higher is required.');
            $requirementsMet = false;
        }

        foreach (['bcmath', 'ctype', 'fileinfo', 'json', 'mbstring', 'openssl', 'pdo', 'pdo_mysql', 'tokenizer'] as $extension) {
            if (! extension_loaded($extension)) {
                $this->components->error("Missing required PHP extension: {$extension}.");
                $requirementsMet = false;
            }
        }

        foreach ([storage_path(), base_path('bootstrap/cache')] as $path) {
            if (! is_writable($path)) {
                $this->components->error("Path is not writable: {$path}");
                $requirementsMet = false;
            }
        }

        return $requirementsMet;
    }

    protected function validateDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable $exception) {
            $this->components->error('Database connection failed. Check your .env database settings.');
            $this->line($exception->getMessage());

            return false;
        }
    }

    protected function ensureApplicationKey(): bool
    {
        if ((string) config('app.key') !== '') {
            return true;
        }

        return $this->call('key:generate', ['--force' => true]) === self::SUCCESS;
    }

    protected function writeInstallReceipt(): void
    {
        File::ensureDirectoryExists(storage_path('app'));

        File::put(storage_path('app/coingrow-install.json'), json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_env' => config('app.env'),
            'app_url' => config('app.url'),
            'seeded_demo' => (bool) $this->option('seed-demo'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function printNextSteps(): void
    {
        $steps = [
            'Point your web server document root to the `public/` directory.',
            'Configure the Laravel scheduler cron: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`',
            'Run a queue worker if you move notifications or analytics to queued jobs.',
        ];

        if (! $this->option('skip-assets')) {
            $steps[] = 'If frontend assets are not already built, run `npm ci && npm run build` on the server or in CI.';
        }

        $steps[] = 'Secure your `.env`, webhook secret, and web server TLS configuration.';

        $this->newLine();
        $this->components->info('COINGROW installation completed.');
        $this->line('Next steps:');

        foreach ($steps as $index => $step) {
            $this->line(($index + 1).'. '.$step);
        }
    }
}
