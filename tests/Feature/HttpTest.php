<?php

declare(strict_types=1);

namespace effina\Larastitial\Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Gate;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Support\Enums\Frequency;
use effina\Larastitial\Support\Enums\InterstitialType;
use effina\Larastitial\Tests\TestCase;

class HttpTest extends TestCase
{
    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function () use ($router) {
            $router->get('/dashboard', fn () => 'dashboard')->name('dashboard');
            $router->post('/dashboard', fn () => 'posted')->name('dashboard.store');
            $router->get('/interstitials-blog', fn () => 'blog')->name('blog');
        });
    }

    public function testAdminIsForbiddenWhenAppDefinesNoGate(): void
    {
        $this->actingAs($this->makeUser())
            ->get('/admin/interstitials')
            ->assertForbidden();

        $this->actingAs($this->makeUser())
            ->post('/admin/interstitials', ['name' => 'nope'])
            ->assertForbidden();

        $this->assertDatabaseMissing('interstitials', ['name' => 'nope']);
    }

    public function testAdminIndexRendersForAuthorizedUser(): void
    {
        $this->allowAdmin();

        $this->actingAs($this->makeUser())
            ->get('/admin/interstitials')
            ->assertOk();
    }

    public function testAdminIsForbiddenWhenGateDenies(): void
    {
        Gate::define('manage-interstitials', fn () => false);

        $this->actingAs($this->makeUser())
            ->get('/admin/interstitials')
            ->assertForbidden();
    }

    public function testAdminCanCreateInterstitial(): void
    {
        $this->allowAdmin();

        $this->actingAs($this->makeUser())
            ->post('/admin/interstitials', [
                'name' => 'welcome',
                'title' => 'Welcome',
                'type' => 'modal',
                'content_type' => 'database',
                'content' => '<p>Hello</p>',
                'audience_type' => 'all',
                'frequency' => 'once',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('interstitials', ['name' => 'welcome']);
    }

    public function testRouteFullPageRedirectsGetRequests(): void
    {
        $interstitial = $this->fullPage();

        $this->get('/dashboard')
            ->assertRedirect('/interstitial/' . $interstitial->uuid);

        $this->get('/interstitial/' . $interstitial->uuid)
            ->assertOk()
            ->assertSee('Read me');
    }

    public function testRouteFullPageDoesNotHijackPostRequests(): void
    {
        $this->fullPage();

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post('/dashboard')
            ->assertOk()
            ->assertSee('posted');
    }

    public function testRoutesSharingThePrefixAreNotSkipped(): void
    {
        $interstitial = $this->fullPage(['blog']);

        $this->get('/interstitials-blog')
            ->assertRedirect('/interstitial/' . $interstitial->uuid);
    }

    public function testAlwaysFullPageDoesNotLoopAfterAction(): void
    {
        $interstitial = $this->fullPage(['dashboard*'], Frequency::Always);
        $page = '/interstitial/' . $interstitial->uuid;

        $this->get('/dashboard')->assertRedirect($page);
        $this->get($page)->assertOk();

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post($page . '/action', ['action' => 'completed'])
            ->assertRedirect('/dashboard');

        // The return trip lands on the page the user originally asked for
        $this->get('/dashboard')->assertOk()->assertSee('dashboard');

        // Later visits still honor the "always" frequency
        $this->get('/dashboard')->assertRedirect($page);
    }

    public function testAlwaysFullPageDoesNotLoopWhenRedirectAfterMatchesTrigger(): void
    {
        $interstitial = $this->fullPage(['dashboard*'], Frequency::Always);
        $interstitial->update(['redirect_after' => '/dashboard']);
        $page = '/interstitial/' . $interstitial->uuid;

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->post($page . '/action', ['action' => 'dismissed'])
            ->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk();
    }

    private function allowAdmin(): void
    {
        Gate::define('manage-interstitials', fn () => true);
    }

    private function fullPage(array $routes = ['dashboard*'], Frequency $frequency = Frequency::Once): Interstitial
    {
        return Interstitial::create([
            'name' => 'terms-' . uniqid(),
            'title' => 'Terms',
            'type' => InterstitialType::FullPage,
            'content' => '<p>Read me</p>',
            'frequency' => $frequency,
            'trigger_routes' => $routes,
        ]);
    }
}
