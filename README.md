# Larastitial

A Laravel package for managing interstitials (modals, full-page redirects, inline content blocks) with configurable triggers, audience targeting, frequency control, and optional admin UI.

## Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, or 12.x

## Installation

```bash
composer require effina/larastitial
```

Run the install command:

```bash
php artisan larastitial:install
```

Or manually publish and migrate:

```bash
php artisan vendor:publish --tag=larastitial-config
php artisan vendor:publish --tag=larastitial-migrations
php artisan migrate
```

### Middleware Registration

The package automatically registers its middleware to the `web` middleware group. If auto-registration doesn't work (common in Laravel 11+), manually register it:

**Laravel 11+ (`bootstrap/app.php`):**

```php
use effina\Larastitial\Http\Middleware\CheckInterstitials;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', CheckInterstitials::class);
    })
    // ...
```

**Laravel 10 (`app/Http/Kernel.php`):**

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \effina\Larastitial\Http\Middleware\CheckInterstitials::class,
    ],
];
```

To verify the middleware is registered:

```php
dd(app('router')->getMiddlewareGroups()['web']);
```

## Quick Start

### 1. Create an Interstitial

Visit `/admin/interstitials` to use the built-in admin UI, or create one programmatically:

```php
use effina\Larastitial\Models\Interstitial;

Interstitial::create([
    'name' => 'welcome-message',
    'title' => 'Welcome!',
    'type' => 'modal',
    'content' => '<p>Thanks for signing up!</p>',
    'trigger_event' => \Illuminate\Auth\Events\Login::class,
    'audience_type' => 'authenticated',
    'frequency' => 'once',
]);
```

### 2. Configure Event Listeners

In `config/larastitial.php`, enable the events you want to trigger interstitials:

```php
'event_listeners' => [
    \Illuminate\Auth\Events\Login::class,
    \Illuminate\Auth\Events\Registered::class,
],
```

### 3. Display in Your Views

For modals, add this to your layout:

```blade
@interstitials('modal')
    <x-larastitial::modal :interstitial="$interstitial" />
@endinterstitials
```

For inline content:

```blade
@interstitial('inline', 'sidebar-promo')
```

## Interstitial Types

### Modal
Overlay dialogs that appear on top of the current page.

### Full Page
Redirects user to a dedicated interstitial page before continuing.

### Inline
Renders content within the page at named slots.

## Triggers

- **Event-based**: Trigger on Laravel events (Login, Registered, custom events)
- **Route-based**: Trigger on specific routes (supports wildcards)
- **Scheduled**: Show only within date/time ranges
- **Manual**: Queue interstitials programmatically

## Audience Targeting

- **All users**: Show to everyone
- **Authenticated**: Logged-in users only
- **Guests**: Non-authenticated visitors only
- **Roles**: Users with specific roles
- **Custom**: Implement your own condition class

## Frequency Control

- **Always**: Show every time conditions are met
- **Once**: Show once per user, ever
- **Once per session**: Show once per browser session
- **Every X days**: Show again after specified days

## Frontend Modes

Configure in `config/larastitial.php`:

```php
'frontend' => 'blade', // blade, livewire, inertia, headless
```

### Headless/API Mode

For SPAs, use the JSON API:

```
GET /api/interstitials/applicable?context=page_load
GET /api/interstitials/{uuid}
POST /api/interstitials/{uuid}/action
POST /api/interstitials/{uuid}/respond
```

## Using the Facade

```php
use effina\Larastitial\Facades\Larastitial;

// Get applicable interstitials
$interstitials = Larastitial::getApplicable($user, 'page_load');

// Check if should show
if (Larastitial::shouldShow($interstitial, $user)) {
    // Display interstitial
}

// Mark as viewed
Larastitial::markViewed($interstitial, $user, 'completed');

// Record form response
Larastitial::recordResponse($interstitial, $user, $request->all());
```

## User Model Trait

Add the trait to your User model for convenience methods:

```php
use effina\Larastitial\Traits\HasInterstitials;

class User extends Authenticatable
{
    use HasInterstitials;
}

// Then use:
$user->hasViewedInterstitial($id);
$user->hasCompletedInterstitial($id);
$user->getViewedInterstitialIds();
```

## Custom Audience Conditions

Create a class implementing `AudienceCondition`:

```php
use effina\Larastitial\Contracts\AudienceCondition;
use effina\Larastitial\Models\Interstitial;

class HasCompletedProfile implements AudienceCondition
{
    public function passes(?Authenticatable $user, Interstitial $interstitial): bool
    {
        return $user && $user->profile_completed_at !== null;
    }
}
```

Then reference it in your interstitial's `audience_condition` field.

## Multi-Tenancy

Enable in config:

```php
'multi_tenant' => [
    'enabled' => true,
    'column' => 'tenant_id',
    'resolver' => \App\Services\TenantResolver::class,
],
```

Implement the resolver:

```php
use effina\Larastitial\Contracts\TenantResolver;

class TenantResolver implements TenantResolver
{
    public function resolve(): int|string|null
    {
        return auth()->user()?->tenant_id;
    }
}
```

## Testing

Use the fake for testing:

```php
use effina\Larastitial\Facades\Larastitial;

public function test_interstitial_triggers_on_login(): void
{
    $fake = Larastitial::fake();

    $interstitial = Interstitial::factory()->create();
    $fake->shouldTrigger($interstitial);

    // ... perform action

    $fake->assertTriggered($interstitial->name);
}
```

Use the factory for creating test data:

```php
use effina\Larastitial\Models\Interstitial;

$interstitial = Interstitial::factory()
    ->modal()
    ->forAuthenticated()
    ->showOnce()
    ->triggeredByEvent(\Illuminate\Auth\Events\Login::class)
    ->create();
```

## Events

Listen to these events for custom logic:

- `InterstitialTriggered` - When an interstitial should be shown
- `InterstitialViewed` - When user sees an interstitial
- `InterstitialDismissed` - When user dismisses
- `InterstitialCompleted` - When user completes (CTA/form)
- `InterstitialResponseSubmitted` - When form is submitted

## Commands

```bash
# Install package
php artisan larastitial:install

# Publish specific resources
php artisan larastitial:publish --config
php artisan larastitial:publish --views
php artisan larastitial:publish --all

# Clean up old view records
php artisan larastitial:cleanup --days=90
```

## Customization

### Publishing Views

```bash
php artisan vendor:publish --tag=larastitial-views
```

Views are published to `resources/views/vendor/larastitial/`.

### Custom Styling

The package ships with minimal, unstyled components. Publish the views and customize to match your application's design system.

## License

MIT License. See [LICENSE](LICENSE) for details.
