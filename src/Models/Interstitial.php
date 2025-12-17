<?php

declare(strict_types=1);

namespace effina\Larastitial\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use effina\Larastitial\Support\Enums\AudienceType;
use effina\Larastitial\Support\Enums\ContentType;
use effina\Larastitial\Support\Enums\Frequency;
use effina\Larastitial\Support\Enums\InterstitialType;
use effina\Larastitial\Support\Enums\QueueBehavior;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $tenant_id
 * @property string $name
 * @property string $title
 * @property InterstitialType $type
 * @property ContentType $content_type
 * @property string|null $content
 * @property string|null $blade_view
 * @property string|null $trigger_event
 * @property array|null $trigger_routes
 * @property \Carbon\Carbon|null $trigger_schedule_start
 * @property \Carbon\Carbon|null $trigger_schedule_end
 * @property AudienceType $audience_type
 * @property array|null $audience_roles
 * @property string|null $audience_condition
 * @property Frequency $frequency
 * @property int|null $frequency_days
 * @property int $priority
 * @property array|null $cta_buttons
 * @property bool $allow_dismiss
 * @property bool $allow_dont_show_again
 * @property string|null $redirect_after
 * @property QueueBehavior $queue_behavior
 * @property string|null $inline_slot
 * @property bool $is_active
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Interstitial extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'type' => InterstitialType::class,
        'content_type' => ContentType::class,
        'audience_type' => AudienceType::class,
        'frequency' => Frequency::class,
        'queue_behavior' => QueueBehavior::class,
        'trigger_routes' => 'array',
        'trigger_schedule_start' => 'datetime',
        'trigger_schedule_end' => 'datetime',
        'audience_roles' => 'array',
        'frequency_days' => 'integer',
        'priority' => 'integer',
        'cta_buttons' => 'array',
        'allow_dismiss' => 'boolean',
        'allow_dont_show_again' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'type' => 'modal',
        'content_type' => 'database',
        'audience_type' => 'all',
        'frequency' => 'once',
        'priority' => 0,
        'allow_dismiss' => true,
        'allow_dont_show_again' => false,
        'queue_behavior' => 'inherit',
        'is_active' => true,
    ];

    public function getTable(): string
    {
        return config('larastitial.tables.interstitials', 'interstitials');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Interstitial $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function views(): HasMany
    {
        return $this->hasMany(InterstitialView::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(InterstitialResponse::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, InterstitialType|string $type): Builder
    {
        $value = $type instanceof InterstitialType ? $type->value : $type;
        return $query->where('type', $value);
    }

    public function scopeModal(Builder $query): Builder
    {
        return $query->ofType(InterstitialType::Modal);
    }

    public function scopeFullPage(Builder $query): Builder
    {
        return $query->ofType(InterstitialType::FullPage);
    }

    public function scopeInline(Builder $query): Builder
    {
        return $query->ofType(InterstitialType::Inline);
    }

    public function scopeForSlot(Builder $query, string $slot): Builder
    {
        return $query->where('inline_slot', $slot);
    }

    public function scopeForEvent(Builder $query, string $eventClass): Builder
    {
        return $query->where('trigger_event', $eventClass);
    }

    public function scopeScheduledNow(Builder $query): Builder
    {
        $now = now();

        return $query->where(function (Builder $q) use ($now) {
            $q->whereNull('trigger_schedule_start')
                ->orWhere('trigger_schedule_start', '<=', $now);
        })->where(function (Builder $q) use ($now) {
            $q->whereNull('trigger_schedule_end')
                ->orWhere('trigger_schedule_end', '>=', $now);
        });
    }

    public function scopeForTenant(Builder $query, int|string|null $tenantId): Builder
    {
        if (!config('larastitial.multi_tenant.enabled')) {
            return $query;
        }

        $column = config('larastitial.multi_tenant.column', 'tenant_id');

        return $query->where(function (Builder $q) use ($column, $tenantId) {
            $q->whereNull($column)->orWhere($column, $tenantId);
        });
    }

    public function scopeByPriority(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('priority', $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Helpers
    |--------------------------------------------------------------------------
    */

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function isModal(): bool
    {
        return $this->type === InterstitialType::Modal;
    }

    public function isFullPage(): bool
    {
        return $this->type === InterstitialType::FullPage;
    }

    public function isInline(): bool
    {
        return $this->type === InterstitialType::Inline;
    }

    public function isScheduled(): bool
    {
        return $this->trigger_schedule_start !== null || $this->trigger_schedule_end !== null;
    }

    public function isWithinSchedule(): bool
    {
        $now = now();

        if ($this->trigger_schedule_start && $now->lt($this->trigger_schedule_start)) {
            return false;
        }

        if ($this->trigger_schedule_end && $now->gt($this->trigger_schedule_end)) {
            return false;
        }

        return true;
    }

    public function matchesRoute(string $route): bool
    {
        if (empty($this->trigger_routes)) {
            return false;
        }

        foreach ($this->trigger_routes as $pattern) {
            if (Str::is($pattern, $route)) {
                return true;
            }
        }

        return false;
    }

    public function getEffectiveQueueBehavior(): string
    {
        if ($this->queue_behavior === QueueBehavior::Inherit) {
            return config('larastitial.queue_behavior', 'configurable');
        }

        return $this->queue_behavior->value;
    }
}
