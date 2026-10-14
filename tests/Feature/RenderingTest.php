<?php

declare(strict_types=1);

namespace effina\Larastitial\Tests\Feature;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Blade;
use effina\Larastitial\Models\Interstitial;
use effina\Larastitial\Support\Enums\ContentType;
use effina\Larastitial\Support\Enums\InterstitialType;
use effina\Larastitial\Tests\TestCase;

class RenderingTest extends TestCase
{
    public function testModalComponentRenders(): void
    {
        $interstitial = Interstitial::create([
            'name' => 'modal',
            'title' => 'Modal Title',
            'content' => '<p>Modal body</p>',
        ]);

        $html = Blade::render('<x-larastitial-modal :interstitial="$i" />', ['i' => $interstitial]);

        $this->assertStringContainsString('Modal body', $html);
        $this->assertStringContainsString('larastitial-modal-' . $interstitial->uuid, $html);
    }

    public function testInlineDirectiveRendersSlotContent(): void
    {
        Interstitial::create([
            'name' => 'inline',
            'title' => 'Inline',
            'type' => InterstitialType::Inline,
            'inline_slot' => 'sidebar',
            'content_type' => ContentType::Database,
            'content' => '<p>Hello {{ $interstitial->title }}</p>',
        ]);

        $this->startSession();

        $html = Blade::render("@interstitial('inline', 'sidebar')");

        $this->assertStringContainsString('Hello Inline', $html);
    }

    public function testLoginEventQueuesInterstitialInSession(): void
    {
        $user = $this->makeUser();
        $interstitial = Interstitial::create([
            'name' => 'login-welcome',
            'title' => 'Welcome back',
            'content' => '<p>Hi</p>',
            'trigger_event' => Login::class,
        ]);

        $this->startSession();

        event(new Login('web', $user, false));

        $queued = session(config('larastitial.session.queued_key'));

        $this->assertSame($interstitial->id, $queued[0]['interstitial_id'] ?? null);
    }
}
