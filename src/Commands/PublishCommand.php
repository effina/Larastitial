<?php

declare(strict_types=1);

namespace effina\Larastitial\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'larastitial:publish
                            {--config : Publish config file}
                            {--migrations : Publish migrations}
                            {--views : Publish views}
                            {--policy : Publish policy stub}
                            {--tests : Publish test stubs}
                            {--all : Publish all resources}
                            {--force : Overwrite existing files}';

    protected $description = 'Publish Larastitial resources';

    public function handle(): int
    {
        $force = $this->option('force');
        $all = $this->option('all');

        if ($all || $this->option('config')) {
            $this->publishConfig($force);
        }

        if ($all || $this->option('migrations')) {
            $this->publishMigrations($force);
        }

        if ($all || $this->option('views')) {
            $this->publishViews($force);
        }

        if ($all || $this->option('policy')) {
            $this->publishPolicy($force);
        }

        if ($all || $this->option('tests')) {
            $this->publishTests($force);
        }

        if (!$all && !$this->option('config') && !$this->option('migrations') && !$this->option('views') && !$this->option('policy') && !$this->option('tests')) {
            $this->warn('No resources specified. Use --all or specify individual resources.');
            $this->line('Available options: --config, --migrations, --views, --policy, --tests, --all');
            return self::FAILURE;
        }

        $this->info('Publishing complete!');

        return self::SUCCESS;
    }

    protected function publishConfig(bool $force): void
    {
        $this->info('Publishing config...');
        $this->call('vendor:publish', [
            '--tag' => 'larastitial-config',
            '--force' => $force,
        ]);
    }

    protected function publishMigrations(bool $force): void
    {
        $this->info('Publishing migrations...');
        $this->call('vendor:publish', [
            '--tag' => 'larastitial-migrations',
            '--force' => $force,
        ]);
    }

    protected function publishViews(bool $force): void
    {
        $this->info('Publishing views...');
        $this->call('vendor:publish', [
            '--tag' => 'larastitial-views',
            '--force' => $force,
        ]);
    }

    protected function publishPolicy(bool $force): void
    {
        $this->info('Publishing policy...');
        $this->call('vendor:publish', [
            '--tag' => 'larastitial-policy',
            '--force' => $force,
        ]);
    }

    protected function publishTests(bool $force): void
    {
        $this->info('Publishing test stubs...');
        $this->call('vendor:publish', [
            '--tag' => 'larastitial-tests',
            '--force' => $force,
        ]);
    }
}
