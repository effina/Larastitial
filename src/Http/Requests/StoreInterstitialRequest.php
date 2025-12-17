<?php

declare(strict_types=1);

namespace effina\Larastitial\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use effina\Larastitial\Support\Enums\AudienceType;
use effina\Larastitial\Support\Enums\ContentType;
use effina\Larastitial\Support\Enums\Frequency;
use effina\Larastitial\Support\Enums\InterstitialType;
use effina\Larastitial\Support\Enums\QueueBehavior;

class StoreInterstitialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:' . config('larastitial.tables.interstitials', 'interstitials')],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(InterstitialType::class)],
            'content_type' => ['required', Rule::enum(ContentType::class)],
            'content' => ['nullable', 'string'],
            'blade_view' => ['nullable', 'string', 'max:255'],
            'trigger_event' => ['nullable', 'string', 'max:255'],
            'trigger_routes' => ['nullable', 'array'],
            'trigger_routes.*' => ['string'],
            'trigger_schedule_start' => ['nullable', 'date'],
            'trigger_schedule_end' => ['nullable', 'date', 'after_or_equal:trigger_schedule_start'],
            'audience_type' => ['required', Rule::enum(AudienceType::class)],
            'audience_roles' => ['nullable', 'array'],
            'audience_roles.*' => ['string'],
            'audience_condition' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', Rule::enum(Frequency::class)],
            'frequency_days' => ['nullable', 'integer', 'min:1'],
            'priority' => ['integer'],
            'cta_buttons' => ['nullable', 'array'],
            'cta_buttons.*.label' => ['required_with:cta_buttons', 'string'],
            'cta_buttons.*.url' => ['nullable', 'string'],
            'cta_buttons.*.style' => ['nullable', 'string'],
            'allow_dismiss' => ['boolean'],
            'allow_dont_show_again' => ['boolean'],
            'redirect_after' => ['nullable', 'string', 'max:255'],
            'queue_behavior' => [Rule::enum(QueueBehavior::class)],
            'inline_slot' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function prepareForValidation(): void
    {
        // Decode JSON strings to arrays for trigger_routes and audience_roles
        $triggerRoutes = $this->input('trigger_routes');
        if (is_string($triggerRoutes)) {
            $triggerRoutes = json_decode($triggerRoutes, true) ?: [];
        }

        $audienceRoles = $this->input('audience_roles');
        if (is_string($audienceRoles)) {
            $audienceRoles = json_decode($audienceRoles, true) ?: [];
        }

        $this->merge([
            'allow_dismiss' => $this->boolean('allow_dismiss', true),
            'allow_dont_show_again' => $this->boolean('allow_dont_show_again', false),
            'is_active' => $this->boolean('is_active', true),
            'priority' => $this->input('priority', 0),
            'queue_behavior' => $this->input('queue_behavior', 'inherit'),
            'trigger_routes' => $triggerRoutes,
            'audience_roles' => $audienceRoles,
        ]);
    }
}
