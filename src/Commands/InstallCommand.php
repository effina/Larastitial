<?php

declare(strict_types=1);

namespace effina\Larastitial\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'larastitial:install
                            {--force : Overwrite existing files}';

    protected $description = 'Install Larastitial package resources';

    public function handle(): int
    {
        $this->info('Installing Larastitial...');

        // Publish config
        $this->call('vendor:publish', [
            '--tag' => 'larastitial-config',
            '--force' => $this->option('force'),
        ]);

        // Publish migrations
        $this->call('vendor:publish', [
            '--tag' => 'larastitial-migrations',
            '--force' => $this->option('force'),
        ]);

        // Run migrations
        if ($this->confirm('Would you like to run the migrations now?', true)) {
            $this->call('migrate');
        }

        // Optionally publish views
        if ($this->confirm('Would you like to publish the views for customization?', false)) {
            $this->call('vendor:publish', [
                '--tag' => 'larastitial-views',
                '--force' => $this->option('force'),
            ]);
        }

        $this->newLine();
        $this->info('Larastitial installed successfully!');
        $this->newLine();

        $this->line('Next steps:');
        $this->line('1. Configure your event listeners in config/larastitial.php');
        $this->line('2. Define a Gate for admin access (optional)');
        $this->line('3. Add the HasInterstitials trait to your User model (optional)');
        $this->line('4. Visit /admin/interstitials to create your first interstitial');

        return self::SUCCESS;
    }
}
