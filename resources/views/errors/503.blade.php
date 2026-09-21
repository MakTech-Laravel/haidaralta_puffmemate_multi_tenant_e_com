<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    @php
        $applicationSettings = app(\App\Services\Application\ApplicationSettingsService::class);
        $shared = $applicationSettings->sharedPayload();
        $appName = (string) config('app.name', 'PuffMeMate');
        $themeColor = (string) ($shared['themeColor'] ?? config('pwa.theme_color', '#C4472F'));
        $backgroundColor = (string) ($shared['backgroundColor'] ?? '#FFFFFF');
        $logoUrl = $shared['logoUrl'] ?? '/images/storefront/logo.png';
        $faviconUrl = $applicationSettings->faviconHref();
        $description = $shared['description'] ?? null;

        $theme = ltrim($themeColor, '#');
        if (strlen($theme) === 3) {
            $theme = $theme[0].$theme[0].$theme[1].$theme[1].$theme[2].$theme[2];
        }
        $r = hexdec(substr($theme, 0, 2));
        $g = hexdec(substr($theme, 2, 2));
        $b = hexdec(substr($theme, 4, 2));
    @endphp
    <title>{{ $appName }} — We'll be right back</title>
    <meta name="theme-color" content="{{ $themeColor }}">
    <link rel="icon" href="{{ $faviconUrl }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: {{ $themeColor }};
            --brand-rgb: {{ $r }}, {{ $g }}, {{ $b }};
            --bg: {{ $backgroundColor }};
            --ink: #1a1412;
            --muted: #6b5e58;
            --surface: rgba(255, 255, 255, 0.72);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            min-height: 100%;
        }

        body {
            font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
            color: var(--ink);
            background: var(--bg);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        .scene {
            position: relative;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: clamp(1.5rem, 4vw, 3rem);
            isolation: isolate;
        }

        .glow {
            position: absolute;
            inset: 0;
            z-index: -2;
            background:
                radial-gradient(ellipse 80% 60% at 50% -10%, rgba(var(--brand-rgb), 0.28), transparent 55%),
                radial-gradient(ellipse 50% 40% at 100% 100%, rgba(var(--brand-rgb), 0.14), transparent 50%),
                radial-gradient(ellipse 40% 35% at 0% 80%, rgba(var(--brand-rgb), 0.1), transparent 45%),
                linear-gradient(180deg, #fff8f5 0%, var(--bg) 45%, #f7f1ee 100%);
        }

        .grid-fade {
            position: absolute;
            inset: 0;
            z-index: -1;
            opacity: 0.35;
            background-image:
                linear-gradient(rgba(var(--brand-rgb), 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(var(--brand-rgb), 0.06) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 70% 60% at 50% 40%, #000 20%, transparent 75%);
            animation: grid-drift 28s linear infinite;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            z-index: -1;
            pointer-events: none;
            animation: float 12s ease-in-out infinite;
        }

        .orb-a {
            width: min(42vw, 320px);
            height: min(42vw, 320px);
            top: 12%;
            right: 8%;
            background: rgba(var(--brand-rgb), 0.22);
            animation-delay: -2s;
        }

        .orb-b {
            width: min(34vw, 240px);
            height: min(34vw, 240px);
            bottom: 10%;
            left: 6%;
            background: rgba(var(--brand-rgb), 0.16);
            animation-delay: -6s;
        }

        .panel {
            width: min(100%, 36rem);
            text-align: center;
            animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        .brand {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.85rem;
            margin-bottom: 1.75rem;
            animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.08s both;
        }

        .brand img {
            height: clamp(2.75rem, 8vw, 3.5rem);
            width: auto;
            max-width: 14rem;
            object-fit: contain;
        }

        .brand-wordmark {
            font-family: 'Fraunces', Georgia, serif;
            font-size: clamp(2rem, 6vw, 2.75rem);
            font-weight: 600;
            letter-spacing: -0.03em;
            line-height: 1;
            color: var(--ink);
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            margin-bottom: 1.1rem;
            padding: 0.4rem 0.85rem 0.4rem 0.55rem;
            border-radius: 999px;
            background: rgba(var(--brand-rgb), 0.1);
            color: var(--brand);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.14s both;
        }

        .pulse {
            position: relative;
            width: 0.65rem;
            height: 0.65rem;
            border-radius: 50%;
            background: var(--brand);
            flex-shrink: 0;
        }

        .pulse::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 2px solid rgba(var(--brand-rgb), 0.45);
            animation: ping 1.8s cubic-bezier(0, 0, 0.2, 1) infinite;
        }

        h1 {
            font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
            font-size: clamp(1.15rem, 3vw, 1.35rem);
            font-weight: 600;
            line-height: 1.35;
            letter-spacing: -0.01em;
            margin-bottom: 0.85rem;
            color: var(--ink);
            animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.2s both;
        }

        h1 em {
            font-style: normal;
            color: var(--brand);
        }

        .lede {
            font-size: clamp(1rem, 2.4vw, 1.125rem);
            line-height: 1.55;
            color: var(--muted);
            max-width: 28rem;
            margin: 0 auto 2rem;
            animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.28s both;
        }

        .meter {
            width: min(100%, 16rem);
            height: 0.35rem;
            margin: 0 auto 2.25rem;
            border-radius: 999px;
            background: rgba(var(--brand-rgb), 0.12);
            overflow: hidden;
            animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.34s both;
        }

        .meter > span {
            display: block;
            height: 100%;
            width: 40%;
            border-radius: inherit;
            background: linear-gradient(90deg, transparent, var(--brand), transparent);
            animation: shimmer 2.4s ease-in-out infinite;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.75rem 1.25rem;
            font-size: 0.875rem;
            color: var(--muted);
            animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.4s both;
        }

        .meta strong {
            color: var(--ink);
            font-weight: 600;
        }

        .footer {
            margin-top: 3rem;
            font-size: 0.8rem;
            color: var(--muted);
            animation: rise 0.85s cubic-bezier(0.22, 1, 0.36, 1) 0.48s both;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(18px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes ping {
            75%, 100% {
                transform: scale(2.1);
                opacity: 0;
            }
        }

        @keyframes shimmer {
            0% { transform: translateX(-120%); }
            100% { transform: translateX(320%); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-18px); }
        }

        @keyframes grid-drift {
            from { background-position: 0 0; }
            to { background-position: 48px 48px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
            }
        }
    </style>
</head>
<body>
    <main class="scene" role="main">
        <div class="glow" aria-hidden="true"></div>
        <div class="grid-fade" aria-hidden="true"></div>
        <div class="orb orb-a" aria-hidden="true"></div>
        <div class="orb orb-b" aria-hidden="true"></div>

        <div class="panel">
            <div class="brand">
                <img src="{{ $logoUrl }}" alt="">
                <span class="brand-wordmark">{{ $appName }}</span>
            </div>

            <div class="status">
                <span class="pulse" aria-hidden="true"></span>
                Temporarily offline
            </div>

            <h1>We'll be <em>right back</em></h1>

            <p class="lede">
                @if (filled($description))
                    {{ $description }} We're doing a quick tune-up so everything runs smoother — hang tight.
                @else
                    We're polishing things up and will reopen shortly. Thanks for your patience.
                @endif
            </p>

            <div class="meter" aria-hidden="true"><span></span></div>

            <div class="meta">
                <span>Status <strong>503</strong></span>
                <span aria-hidden="true">·</span>
                <span>This page refreshes automatically</span>
            </div>

            <p class="footer">&copy; {{ date('Y') }} {{ $appName }}</p>
        </div>
    </main>
</body>
</html>
