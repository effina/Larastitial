<?php

declare(strict_types=1);

namespace effina\Larastitial;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use effina\Larastitial\Commands\CleanupViewsCommand;
use effina\Larastitial\Commands\InstallCommand;
use effina\Larastitial\Commands\PublishCommand;
use effina\Larastitial\Contracts\AudienceCondition;
use effina\Larastitial\Contracts\ContentRenderer as ContentRendererContract;
use effina\Larastitial\Contracts\TenantResolver;
use effina\Larastitial\Http\Middleware\CheckInterstitials;
use effina\Larastitial\Listeners\TriggerInterstitialOnEvent;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Policies\InterstitialPolicy;
use effina\Larastitial\Services\AudienceResolver;
use effina\Larastitial\Services\ContentRenderer;
use effina\Larastitial\Services\FrequencyChecker;
use effina\Larastitial\Services\InterstitialManager;
use effina\Larastitial\View\Components\Inline;
use effina\Larastitial\View\Components\Modal;

class LarastitialServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/larastitial.php', 'larastitial');

        $this->registerServices();
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerEventListeners();
        $this->registerBladeDirectives();
        $this->registerBladeComponents();
        $this->registerPolicies();
        $this->registerGates();
        $this->registerCommands();
    }

    protected function registerServices(): void
    {
        $this->app->singleton(InterstitialManager::class, function ($app) {
            return new InterstitialManager(
                $app->make(AudienceResolver::class),
                $app->make(FrequencyChecker::class),
                $app->make(ContentRendererContract::class)
            );
        });

        $this->app->alias(InterstitialManager::class, 'larastitial');

        $this->app->singleton(AudienceResolver::class, function ($app) {
            $tenantResolver = null;
            if (config('larastitial.multi_tenant.enabled') && config('larastitial.multi_tenant.resolver')) {
                $tenantResolver = $app->make(config('larastitial.multi_tenant.resolver'));
            }
            return new AudienceResolver($tenantResolver);
        });

        $this->app->singleton(FrequencyChecker::class, function ($app) {
            return new FrequencyChecker(
                config('larastitial.tracking_storage', 'database'),
                config('larastitial.cache_prefix', 'larastitial')
            );
        });

        $this->app->singleton(ContentRendererContract::class, ContentRenderer::class);
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/larastitial.php' => config_path('larastitial.php'),
            ], 'larastitial-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'larastitial-migrations');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/larastitial'),
            ], 'larastitial-views');

            $this->publishes([
                __DIR__ . '/../stubs/InterstitialPolicy.php.stub' => app_path('Policies/InterstitialPolicy.php'),
            ], 'larastitial-policy');

            $this->publishes([
                __DIR__ . '/../stubs/tests/InterstitialExampleTest.php.stub' => base_path('tests/Feature/InterstitialExampleTest.php'),
            ], 'larastitial-tests');
        }
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });

        if (config('larastitial.frontend') === 'headless') {
            Route::group($this->apiRouteConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            });
        }

        if (config('larastitial.admin.enabled', true)) {
            Route::group($this->adminRouteConfiguration(), function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');
            });
        }
    }

    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('larastitial.full_page.route_prefix', 'interstitial'),
            'middleware' => config('larastitial.middleware_groups', ['web']),
        ];
    }

    protected function apiRouteConfiguration(): array
    {
        return [
            'prefix' => 'api/interstitials',
            'middleware' => ['api'],
        ];
    }

    protected function adminRouteConfiguration(): array
    {
        return [
            'prefix' => config('larastitial.admin.prefix', 'admin/interstitials'),
            'middleware' => config('larastitial.admin.middleware', ['web', 'auth']),
            'as' => 'larastitial.admin.',
        ];
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('larastitial', CheckInterstitials::class);

        // Defer middleware group registration until app is booted
        // This ensures middleware groups exist before we try to add to them
        $this->app->booted(function () use ($router) {
            $groups = config('larastitial.middleware_groups', ['web']);

            foreach ($groups as $group) {
                if (method_exists($router, 'pushMiddlewareToGroup')) {
                    $router->pushMiddlewareToGroup($group, CheckInterstitials::class);
                }
            }
        });
    }

    protected function registerEventListeners(): void
    {
        $events = config('larastitial.event_listeners', []);

        foreach ($events as $event) {
            Event::listen($event, TriggerInterstitialOnEvent::class);
        }
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('interstitials', function ($expression) {
            return "<?php \$__interstitials = app('larastitial')->getQueued({$expression}); foreach(\$__interstitials as \$interstitial): ?>";
        });

        Blade::directive('endinterstitials', function () {
            return "<?php endforeach; ?>";
        });

        Blade::directive('interstitial', function ($expression) {
            return "<?php echo app('larastitial')->renderInline({$expression}); ?>";
        });

        Blade::directive('endinterstitial', function () {
            return "";
        });
    }

    protected function registerBladeComponents(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'larastitial');

        Blade::component('larastitial-modal', Modal::class);
        Blade::component('larastitial-inline', Inline::class);

        Blade::componentNamespace('effina\\Larastitial\\View\\Components', 'larastitial');
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Interstitial::class, InterstitialPolicy::class);
    }

    protected function registerGates(): void
    {
        $gateName = config('larastitial.admin.gate', 'manage-interstitials');

        if (!Gate::has($gateName)) {
            Gate::define($gateName, function ($user) {
                return true;
            });
        }
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                PublishCommand::class,
                CleanupViewsCommand::class,
            ]);
        }
    }
}
