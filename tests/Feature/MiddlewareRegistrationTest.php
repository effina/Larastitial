<?php

declare(strict_types=1);

namespace effina\Larastitial\Tests\Feature;

use Illuminate\Contracts\Http\Kernel as HttpKernel;
use effina\Larastitial\Http\Middleware\CheckInterstitials;
use effina\Larastitial\Tests\TestCase;

class MiddlewareRegistrationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('larastitial.middleware_groups', ['web', 'router-only']);
        $app['router']->middlewareGroup('router-only', []);
    }

    public function testRegistersOnKernelAndRouterOnlyGroups(): void
    {
        $this->assertContains(CheckInterstitials::class, $this->app['router']->getMiddlewareGroups()['router-only']);

        $kernelGroups = $this->app[HttpKernel::class]->getMiddlewareGroups();
        $this->assertSame(1, count(array_keys($kernelGroups['web'], CheckInterstitials::class, true)));
    }
}
