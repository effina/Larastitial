<?php

declare(strict_types=1);

namespace effina\Larastitial\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $interstitial_id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property array $data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InterstitialResponse extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'array',
    ];

    public function getTable(): string
    {
        return config('larastitial.tables.responses', 'interstitial_responses');
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

    /*
    |--------------------------------------------------------------------------
    | Accessors & Helpers
    |--------------------------------------------------------------------------
    */

    public function getValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function hasValue(string $key): bool
    {
        return array_key_exists($key, $this->data ?? []);
    }
}
