<?php

declare(strict_types=1);

namespace effina\Larastitial\Testing;

use Illuminate\Database\Eloquent\Factories\Factory;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Support\Enums\AudienceType;
use effina\Larastitial\Support\Enums\ContentType;
use effina\Larastitial\Support\Enums\Frequency;
use effina\Larastitial\Support\Enums\InterstitialType;
use effina\Larastitial\Support\Enums\QueueBehavior;

/**
 * @extends Factory<Interstitial>
 */
class InterstitialFactory extends Factory
{
    protected $model = Interstitial::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->slug(3),
            'title' => $this->faker->sentence(4),
            'type' => InterstitialType::Modal,
            'content_type' => ContentType::Database,
            'content' => '<p>' . $this->faker->paragraph() . '</p>',
            'audience_type' => AudienceType::All,
            'frequency' => Frequency::Once,
            'priority' => $this->faker->numberBetween(0, 100),
            'allow_dismiss' => true,
            'allow_dont_show_again' => false,
            'queue_behavior' => QueueBehavior::Inherit,
            'is_active' => true,
        ];
    }

    /**
     * Create a modal interstitial.
     */
    public function modal(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => InterstitialType::Modal,
        ]);
    }

    /**
     * Create a full-page interstitial.
     */
    public function fullPage(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => InterstitialType::FullPage,
        ]);
    }

    /**
     * Create an inline interstitial.
     */
    public function inline(?string $slot = null): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => InterstitialType::Inline,
            'inline_slot' => $slot ?? $this->faker->slug(2),
        ]);
    }

    /**
     * Target authenticated users only.
     */
    public function forAuthenticated(): self
    {
        return $this->state(fn (array $attributes) => [
            'audience_type' => AudienceType::Authenticated,
        ]);
    }

    /**
     * Target guests only.
     */
    public function forGuests(): self
    {
        return $this->state(fn (array $attributes) => [
            'audience_type' => AudienceType::Guest,
        ]);
    }

    /**
     * Target specific roles.
     */
    public function forRoles(array $roles): self
    {
        return $this->state(fn (array $attributes) => [
            'audience_type' => AudienceType::Roles,
            'audience_roles' => $roles,
        ]);
    }

    /**
     * Target with a custom condition.
     */
    public function forCustomCondition(string $conditionClass): self
    {
        return $this->state(fn (array $attributes) => [
            'audience_type' => AudienceType::Custom,
            'audience_condition' => $conditionClass,
        ]);
    }

    /**
     * Show once ever.
     */
    public function showOnce(): self
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => Frequency::Once,
        ]);
    }

    /**
     * Show every time.
     */
    public function showAlways(): self
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => Frequency::Always,
        ]);
    }

    /**
     * Show once per session.
     */
    public function showOncePerSession(): self
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => Frequency::OncePerSession,
        ]);
    }

    /**
     * Show every X days.
     */
    public function showEveryDays(int $days): self
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => Frequency::EveryXDays,
            'frequency_days' => $days,
        ]);
    }

    /**
     * Trigger on a specific event.
     */
    public function triggeredByEvent(string $eventClass): self
    {
        return $this->state(fn (array $attributes) => [
            'trigger_event' => $eventClass,
        ]);
    }

    /**
     * Trigger on specific routes.
     */
    public function triggeredByRoutes(array $routes): self
    {
        return $this->state(fn (array $attributes) => [
            'trigger_routes' => $routes,
        ]);
    }

    /**
     * Schedule the interstitial.
     */
    public function scheduled(?\DateTimeInterface $start = null, ?\DateTimeInterface $end = null): self
    {
        return $this->state(fn (array $attributes) => [
            'trigger_schedule_start' => $start ?? now(),
            'trigger_schedule_end' => $end ?? now()->addDays(30),
        ]);
    }

    /**
     * Create as inactive.
     */
    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set priority.
     */
    public function priority(int $priority): self
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }

    /**
     * Add CTA buttons.
     */
    public function withButtons(array $buttons): self
    {
        return $this->state(fn (array $attributes) => [
            'cta_buttons' => $buttons,
        ]);
    }

    /**
     * Use blade view content.
     */
    public function withBladeView(string $viewName): self
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => ContentType::BladeView,
            'blade_view' => $viewName,
            'content' => null,
        ]);
    }

    /**
     * Create as a form.
     */
    public function asForm(?string $formHtml = null): self
    {
        return $this->state(fn (array $attributes) => [
            'content_type' => ContentType::Form,
            'content' => $formHtml ?? '<form><input type="text" name="feedback"><button type="submit">Submit</button></form>',
        ]);
    }

    /**
     * Set exclusive queue behavior.
     */
    public function exclusive(): self
    {
        return $this->state(fn (array $attributes) => [
            'queue_behavior' => QueueBehavior::Exclusive,
        ]);
    }

    /**
     * Allow "don't show again" option.
     */
    public function withDontShowAgain(): self
    {
        return $this->state(fn (array $attributes) => [
            'allow_dont_show_again' => true,
        ]);
    }

    /**
     * Prevent dismissal.
     */
    public function mandatory(): self
    {
        return $this->state(fn (array $attributes) => [
            'allow_dismiss' => false,
        ]);
    }

    /**
     * Assign to a tenant.
     */
    public function forTenant(int|string $tenantId): self
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }
}
