{{--
    Larastitial Inline Component

    This is a base template. You should publish and customize this view
    to match your application's styling.

    Available variables:
    - $interstitial: The Interstitial model instance
    - $class: Additional CSS classes
--}}
<div
    class="larastitial-inline {{ $class }}"
    data-interstitial-uuid="{{ $interstitial->uuid }}"
>
    @if($interstitial->title)
        <h3 class="larastitial-inline-title">{{ $interstitial->title }}</h3>
    @endif

    <div class="larastitial-inline-content">
        {!! app('larastitial')->render($interstitial) !!}
    </div>

    @if($interstitial->cta_buttons)
        <div class="larastitial-inline-actions">
            @foreach($interstitial->cta_buttons as $button)
                <a
                    href="{{ $button['url'] ?? '#' }}"
                    class="larastitial-inline-btn {{ $button['style'] ?? '' }}"
                    @if(empty($button['url']))
                        onclick="larastitialInlineAction('{{ $interstitial->uuid }}', '{{ $button['id'] ?? $button['label'] }}'); return false;"
                    @endif
                >
                    {{ $button['label'] }}
                </a>
            @endforeach
        </div>
    @endif

    @if($interstitial->allow_dismiss)
        <button
            type="button"
            class="larastitial-inline-dismiss"
            onclick="this.closest('.larastitial-inline').remove(); larastitialInlineDismiss('{{ $interstitial->uuid }}');"
            aria-label="Dismiss"
        >
            &times;
        </button>
    @endif
</div>

@once
<script>
    function larastitialInlineDismiss(uuid) {
        fetch(`/{{ config('larastitial.full_page.route_prefix', 'interstitial') }}/${uuid}/action`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ action: 'dismissed' })
        });
    }

    function larastitialInlineAction(uuid, cta) {
        fetch(`/{{ config('larastitial.full_page.route_prefix', 'interstitial') }}/${uuid}/action`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ action: 'completed', cta })
        });
    }
</script>

<style>
    .larastitial-inline {
        position: relative;
        padding: 1rem;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        margin: 1rem 0;
    }

    .larastitial-inline-title {
        margin: 0 0 0.5rem;
        font-size: 1rem;
        font-weight: 600;
    }

    .larastitial-inline-content {
        font-size: 0.875rem;
        color: #4b5563;
    }

    .larastitial-inline-actions {
        margin-top: 1rem;
        display: flex;
        gap: 0.5rem;
    }

    .larastitial-inline-btn {
        display: inline-block;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 4px;
        text-decoration: none;
        background: #3b82f6;
        color: white;
    }

    .larastitial-inline-dismiss {
        position: absolute;
        top: 0.5rem;
        right: 0.5rem;
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: #9ca3af;
        line-height: 1;
    }
</style>
@endonce
