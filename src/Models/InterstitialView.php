<?php

declare(strict_types=1);

namespace effina\Larastitial\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use effina\Larastitial\Support\Enums\ViewAction;

/**
 * @property int $id
 * @property int $interstitial_id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property ViewAction $action
 * @property \Carbon\Carbon $viewed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InterstitialView extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'action' => ViewAction::class,
        'viewed_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('larastitial.tables.views', 'interstitial_views');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function interstitial(): BelongsTo
    {
        return $this->belongsTo(Interstitial::class);
    }

    public function user(): BelongsTo
    {
        $userModel = config('larastitial.user_model', 'App\\Models\\User');
        return $this->belongsTo($userModel);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForUser(Builder $query, int|string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeForUserOrSession(Builder $query, int|string|null $userId, ?string $sessionId): Builder
    {
        return $query->where(function (Builder $q) use ($userId, $sessionId) {
            if ($userId) {
                $q->where('user_id', $userId);
            }
            if ($sessionId) {
                $q->orWhere('session_id', $sessionId);
            }
        });
    }

    public function scopeWithAction(Builder $query, ViewAction|string $action): Builder
    {
        $value = $action instanceof ViewAction ? $action->value : $action;
        return $query->where('action', $value);
    }

    public function scopeDismissed(Builder $query): Builder
    {
        return $query->withAction(ViewAction::Dismissed);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->withAction(ViewAction::Completed);
    }

    public function scopeDontShowAgain(Builder $query): Builder
    {
        return $query->withAction(ViewAction::DontShowAgain);
    }

    public function scopeViewedAfter(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query->where('viewed_at', '>=', $date);
    }

    public function scopeViewedBefore(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query->where('viewed_at', '<=', $date);
    }
}
