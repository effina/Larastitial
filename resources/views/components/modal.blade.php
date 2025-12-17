{{--
    Larastitial Modal Component

    This is a base template. You should publish and customize this view
    to match your application's modal implementation (Bootstrap, Tailwind UI, Alpine, etc.)

    Available variables:
    - $interstitial: The Interstitial model instance
    - $id: The modal element ID
    - $class: Additional CSS classes
--}}
<div
    id="{{ $id }}"
    class="larastitial-modal {{ $class }}"
    data-interstitial-uuid="{{ $interstitial->uuid }}"
    data-allow-dismiss="{{ $interstitial->allow_dismiss ? 'true' : 'false' }}"
    data-allow-dont-show-again="{{ $interstitial->allow_dont_show_again ? 'true' : 'false' }}"
>
    <div class="larastitial-modal-backdrop"></div>
    <div class="larastitial-modal-content">
        @if($interstitial->title)
            <div class="larastitial-modal-header">
                <h2 class="larastitial-modal-title">{{ $interstitial->title }}</h2>
                @if($interstitial->allow_dismiss)
                    <button
                        type="button"
                        class="larastitial-modal-close"
                        onclick="larastitialDismiss('{{ $interstitial->uuid }}')"
                        aria-label="Close"
                    >
                        &times;
                    </button>
                @endif
            </div>
        @endif

        <div class="larastitial-modal-body">
            {!! app('larastitial')->render($interstitial) !!}
        </div>

        @if($interstitial->cta_buttons || $interstitial->allow_dismiss || $interstitial->allow_dont_show_again)
            <div class="larastitial-modal-footer">
                @if($interstitial->allow_dont_show_again)
                    <button
                        type="button"
                        class="larastitial-btn larastitial-btn-link"
                        onclick="larastitialDontShowAgain('{{ $interstitial->uuid }}')"
                    >
                        Don't show again
                    </button>
                @endif

                @if($interstitial->cta_buttons)
                    @foreach($interstitial->cta_buttons as $button)
                        <button
                            type="button"
                            class="larastitial-btn {{ $button['style'] ?? 'larastitial-btn-primary' }}"
                            onclick="larastitialAction('{{ $interstitial->uuid }}', 'completed', '{{ $button['id'] ?? $button['label'] }}')"
                            @if(!empty($button['url'])) data-redirect="{{ $button['url'] }}" @endif
                        >
                            {{ $button['label'] }}
                        </button>
                    @endforeach
                @endif

                @if($interstitial->allow_dismiss && !$interstitial->cta_buttons)
                    <button
                        type="button"
                        class="larastitial-btn larastitial-btn-secondary"
                        onclick="larastitialDismiss('{{ $interstitial->uuid }}')"
                    >
                        Close
                    </button>
                @endif
            </div>
        @endif
    </div>
</div>

@once
<script>
    function larastitialDismiss(uuid) {
        larastitialAction(uuid, 'dismissed');
    }

    function larastitialDontShowAgain(uuid) {
        larastitialAction(uuid, 'dont_show_again');
    }

    function larastitialAction(uuid, action, cta = null) {
        const modal = document.querySelector(`[data-interstitial-uuid="${uuid}"]`);

        fetch(`/{{ config('larastitial.full_page.route_prefix', 'interstitial') }}/${uuid}/action`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ action, cta })
        })
        .then(response => response.json())
        .then(data => {
            if (modal) {
                modal.style.display = 'none';
                modal.remove();
            }
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        })
        .catch(() => {
            if (modal) {
                modal.style.display = 'none';
            }
        });
    }
</script>

<style>
    .larastitial-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .larastitial-modal-backdrop {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
    }

    .larastitial-modal-content {
        position: relative;
        background: white;
        border-radius: 8px;
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow: auto;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .larastitial-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .larastitial-modal-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
    }

    .larastitial-modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        line-height: 1;
        color: #6b7280;
    }

    .larastitial-modal-body {
        padding: 1.5rem;
    }

    .larastitial-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        padding: 1rem 1.5rem;
        border-top: 1px solid #e5e7eb;
    }

    .larastitial-btn {
        padding: 0.5rem 1rem;
        border-radius: 4px;
        font-size: 0.875rem;
        cursor: pointer;
        border: 1px solid transparent;
    }

    .larastitial-btn-primary {
        background: #3b82f6;
        color: white;
    }

    .larastitial-btn-secondary {
        background: #f3f4f6;
        color: #374151;
        border-color: #d1d5db;
    }

    .larastitial-btn-link {
        background: none;
        color: #6b7280;
        padding-left: 0;
    }
</style>
@endonce
