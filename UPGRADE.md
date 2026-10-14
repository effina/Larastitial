# Upgrade Guide

## 1.x to 2.0

### Admin access is denied by default (required action)

In 1.x, if your application did not define the `manage-interstitials` gate, Larastitial defined one that allowed **every authenticated user** into the admin UI. Because interstitial content can contain Blade, which executes on the server, this let any logged-in user run code on your server.

2.0 no longer defines that gate. Until your application defines it, the admin UI returns `403 Forbidden` for everyone.

If you already define the gate, no change is needed. Otherwise, add it to `AppServiceProvider::boot()` (or wherever you register gates), limited to trusted users:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('manage-interstitials', fn ($user) => $user->is_admin);
```

If you changed `admin.gate` in `config/larastitial.php`, define the gate under that name instead.

With `APP_DEBUG=true`, the 403 page names the missing gate to make this easy to spot.

### Other changes

These need no action, but may change behavior you relied on:

- **Laravel 13 support.** Laravel 10 through 13 are supported.
- **Middleware auto-registration now works on Laravel 11+.** If you registered `CheckInterstitials` manually in `bootstrap/app.php`, it is not added twice. You can remove your manual registration.
- **Full-page interstitials only redirect `GET` requests.** Form submissions are no longer interrupted; queued interstitials wait for the next page load.
- **No redirect loop after a full-page interstitial.** The page the user returns to after acting on one is not interrupted by that same interstitial again.
- **"Every X days" frequency works again** on Laravel 11+ (it previously never re-showed).
- **Cache tracking stores timestamps** instead of date objects, which new Laravel 13 apps refuse to unserialize. On such apps, "last viewed" entries cached by 1.x count as never viewed until they expire.
- **Routes that merely share the interstitial prefix** (e.g. `/interstitials-blog`) are no longer skipped by the middleware.
- New optional config key `session.passed_key`. Published configs without it fall back to the default.
