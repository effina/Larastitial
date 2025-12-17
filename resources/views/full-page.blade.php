{{--
    Larastitial Full-Page View

    This is a base template for full-page interstitials.
    Publish and customize this view to match your application's design.

    Available variables:
    - $interstitial: The Interstitial model instance
    - $content: The rendered content
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $interstitial->title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
        }

        .larastitial-fullpage {
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }

        .larastitial-fullpage-header {
            padding: 2rem 2rem 0;
        }

        .larastitial-fullpage-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .larastitial-fullpage-body {
            padding: 1.5rem 2rem;
            color: #4b5563;
            line-height: 1.6;
        }

        .larastitial-fullpage-footer {
            padding: 1.5rem 2rem 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .larastitial-fullpage-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .larastitial-fullpage-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .larastitial-fullpage-btn-primary {
            background: #3b82f6;
            color: white;
        }

        .larastitial-fullpage-btn-primary:hover {
            background: #2563eb;
        }

        .larastitial-fullpage-btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .larastitial-fullpage-btn-secondary:hover {
            background: #e5e7eb;
        }

        .larastitial-fullpage-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: #9ca3af;
        }

        .larastitial-fullpage-link {
            color: #6b7280;
            text-decoration: none;
        }

        .larastitial-fullpage-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="larastitial-fullpage">
        <div class="larastitial-fullpage-header">
            <h1 class="larastitial-fullpage-title">{{ $interstitial->title }}</h1>
        </div>

        <div class="larastitial-fullpage-body">
            {!! $content !!}
        </div>

        <div class="larastitial-fullpage-footer">
            <form method="POST" action="{{ route('larastitial.action', $interstitial->uuid) }}">
                @csrf
                <input type="hidden" name="action" value="completed">

                <div class="larastitial-fullpage-actions">
                    @if($interstitial->cta_buttons)
                        @foreach($interstitial->cta_buttons as $index => $button)
                            <button
                                type="submit"
                                name="cta"
                                value="{{ $button['id'] ?? $button['label'] }}"
                                class="larastitial-fullpage-btn {{ $index === 0 ? 'larastitial-fullpage-btn-primary' : 'larastitial-fullpage-btn-secondary' }}"
                            >
                                {{ $button['label'] }}
                            </button>
                        @endforeach
                    @else
                        <button type="submit" class="larastitial-fullpage-btn larastitial-fullpage-btn-primary">
                            Continue
                        </button>
                    @endif
                </div>
            </form>

            <div class="larastitial-fullpage-meta">
                @if($interstitial->allow_dismiss)
                    <form method="POST" action="{{ route('larastitial.action', $interstitial->uuid) }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="action" value="dismissed">
                        <button type="submit" class="larastitial-fullpage-link" style="background: none; border: none; cursor: pointer;">
                            Skip for now
                        </button>
                    </form>
                @else
                    <span></span>
                @endif

                @if($interstitial->allow_dont_show_again)
                    <form method="POST" action="{{ route('larastitial.action', $interstitial->uuid) }}" style="display: inline;">
                        @csrf
                        <input type="hidden" name="action" value="dont_show_again">
                        <button type="submit" class="larastitial-fullpage-link" style="background: none; border: none; cursor: pointer;">
                            Don't show again
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
