<?php

declare(strict_types=1);

namespace effina\Larastitial\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use effina\Larastitial\LarastitialServiceProvider;
use effina\Larastitial\Tests\Fixtures\TestUser;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [LarastitialServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('larastitial.user_model', TestUser::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamps();
        });
    }

    protected function makeUser(): TestUser
    {
        return TestUser::query()->create([
            'name' => 'Test',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => 'secret',
        ]);
    }
}
