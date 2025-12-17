<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Frontend Stack
    |--------------------------------------------------------------------------
    |
    | Configure which frontend stack you're using. This affects how
    | interstitials are rendered and what helpers are available.
    |
    | Supported: "blade", "livewire", "inertia", "headless"
    |
    */
    'frontend' => env('LARASTITIAL_FRONTEND', 'blade'),

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of your User model. This is used for
    | audience targeting and tracking who has viewed interstitials.
    |
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | Enable multi-tenancy support to scope interstitials to specific
    | tenants/organizations. When enabled, you must provide a resolver
    | class that implements TenantResolver to determine the current tenant.
    |
    */
    'multi_tenant' => [
        'enabled' => false,
        'column' => 'tenant_id',
        'resolver' => null, // Class that implements effina\Larastitial\Contracts\TenantResolver
    ],

    /*
    |--------------------------------------------------------------------------
    | Tracking Storage
    |--------------------------------------------------------------------------
    |
    | Configure how interstitial views are tracked. Database storage is
    | persistent and queryable. Cache is faster but can be cleared.
    | Use "both" to write to both and read from cache with DB fallback.
    |
    | Supported: "database", "cache", "both"
    |
    */
    'tracking_storage' => env('LARASTITIAL_TRACKING', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure cache settings for interstitial tracking when using
    | cache or both storage modes.
    |
    */
    'cache_prefix' => 'larastitial',
    'cache_ttl' => 60 * 60 * 24 * 30, // 30 days

    /*
    |--------------------------------------------------------------------------
    | Form Response Storage
    |--------------------------------------------------------------------------
    |
    | Configure how form submissions from interstitials are stored.
    | "database" stores in interstitial_responses table.
    | "event" fires an event for custom handling.
    | "both" does both.
    |
    | Supported: "database", "event", "both"
    |
    */
    'form_storage' => 'both',

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    |
    | Customize the database table names used by Larastitial. This is useful
    | if you have naming conventions or conflicts with existing tables.
    |
    */
    'tables' => [
        'interstitials' => 'interstitials',
        'views' => 'interstitial_views',
        'responses' => 'interstitial_responses',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Behavior
    |--------------------------------------------------------------------------
    |
    | Configure how multiple applicable interstitials are handled.
    | "all" shows all in sequence (user dismisses one, next appears).
    | "priority" shows only the highest priority interstitial.
    | "configurable" respects per-interstitial queue_behavior setting.
    |
    | Supported: "all", "priority", "configurable"
    |
    */
    'queue_behavior' => 'configurable',

    /*
    |--------------------------------------------------------------------------
    | Admin UI
    |--------------------------------------------------------------------------
    |
    | Configure the built-in admin interface for managing interstitials.
    | Set enabled to false to disable the admin routes entirely.
    |
    */
    'admin' => [
        'enabled' => true,
        'prefix' => 'admin/interstitials',
        'middleware' => ['web', 'auth'],
        'gate' => 'manage-interstitials',
    ],

    /*
    |--------------------------------------------------------------------------
    | Full-Page Interstitial Settings
    |--------------------------------------------------------------------------
    |
    | Configure behavior for full-page interstitials that redirect users
    | to a dedicated interstitial page.
    |
    */
    'full_page' => [
        'route_prefix' => 'interstitial',
        'redirect_to_original' => true,
        'session_key' => 'larastitial_intended_url',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Listeners
    |--------------------------------------------------------------------------
    |
    | List Laravel event classes that should trigger interstitial checks.
    | Common events include Login, Registered, Verified, etc.
    |
    | Example:
    | 'event_listeners' => [
    |     \Illuminate\Auth\Events\Login::class,
    |     \Illuminate\Auth\Events\Registered::class,
    | ],
    |
    */
    'event_listeners' => [
        // \Illuminate\Auth\Events\Login::class,
        // \Illuminate\Auth\Events\Registered::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Groups
    |--------------------------------------------------------------------------
    |
    | Define which middleware groups should have the CheckInterstitials
    | middleware automatically applied. This enables route-based and
    | page-load interstitial triggers.
    |
    */
    'middleware_groups' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Configure session-related settings for interstitial tracking.
    |
    */
    'session' => [
        'queued_key' => 'larastitial_queued',
        'viewed_key' => 'larastitial_viewed_this_session',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Editor
    |--------------------------------------------------------------------------
    |
    | Configure the WYSIWYG editor used in the admin interface.
    | Quill.js is bundled by default.
    |
    */
    'editor' => [
        'driver' => 'quill',
        'options' => [
            'theme' => 'snow',
            'modules' => [
                'toolbar' => [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [['header' => 1], ['header' => 2]],
                    [['list' => 'ordered'], ['list' => 'bullet']],
                    [['indent' => '-1'], ['indent' => '+1']],
                    ['link', 'image'],
                    ['clean'],
                ],
            ],
        ],
    ],
];
