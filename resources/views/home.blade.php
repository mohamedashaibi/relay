<!doctype html>
<html lang="{{ $locale }}" dir="{{ $rtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ __('home.meta.title') }}</title>
    <meta name="description" content="{{ __('home.meta.description') }}">
    <link rel="canonical" href="{{ $locale === 'en' ? url('/') : url('/ar') }}">
    <link rel="alternate" hreflang="en" href="{{ url('/') }}">
    <link rel="alternate" hreflang="ar" href="{{ url('/ar') }}">
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Relay">
    <meta property="og:title" content="{{ __('home.meta.title') }}">
    <meta property="og:description" content="{{ __('home.meta.description') }}">
    <meta property="og:url" content="{{ $locale === 'en' ? url('/') : url('/ar') }}">
    <meta property="og:locale" content="{{ $rtl ? 'ar_LY' : 'en_US' }}">
    <meta name="twitter:card" content="summary">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%230f766e'/%3E%3Cpath d='M10 22V10h7a4 4 0 0 1 0 8h-7m7 0 5 4' stroke='white' stroke-width='3' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=bricolage-grotesque:500,700,800|plus-jakarta-sans:300,400,500,600,700|jetbrains-mono:500{{ $rtl ? '|noto-kufi-arabic:500,700,800|ibm-plex-sans-arabic:300,400,500,600' : '' }}&display=swap">
    <script>
        try {
            const t = localStorage.getItem('relay.theme');
            if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t;
        } catch (e) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/home.js'])
</head>
<body class="home">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:start-3 focus:z-50 focus:rounded-lg focus:bg-surface focus:px-4 focus:py-2 focus:ring-2 focus:ring-brand">
        {{ $rtl ? 'انتقل إلى المحتوى' : 'Skip to content' }}
    </a>

    {{-- ── Navigation ─────────────────────────────────────────── --}}
    <header class="sticky top-0 z-40 border-b border-transparent bg-canvas/85 backdrop-blur transition-colors duration-300" data-nav>
        <nav class="mx-auto flex h-16 max-w-[1200px] items-center gap-8 px-5 sm:px-8" aria-label="{{ $rtl ? 'الرئيسية' : 'Main' }}">
            <a href="{{ $locale === 'en' ? url('/') : url('/ar') }}" class="wordmark font-display text-[26px] leading-none font-extrabold tracking-[-0.05em]" dir="ltr">Relay<span class="text-brand">.</span></a>

            <ul class="hidden items-center gap-7 text-[14px] font-medium text-ink-2 md:flex">
                <li><a href="#features" class="transition-colors hover:text-ink">{{ __('home.nav.features') }}</a></li>
                <li><a href="#how" class="transition-colors hover:text-ink">{{ __('home.nav.how') }}</a></li>
                <li><a href="#clients" class="transition-colors hover:text-ink">{{ __('home.nav.clients') }}</a></li>
                <li><a href="#faq" class="transition-colors hover:text-ink">{{ __('home.nav.faq') }}</a></li>
            </ul>

            <div class="ms-auto flex items-center gap-2">
                <a href="{{ url('/'.$other) }}" hreflang="{{ $other }}" lang="{{ $other }}" class="btn btn-quiet btn-sm" aria-label="{{ __('home.nav.switch_label') }}">
                    {{ __('home.nav.switch') }}
                </a>
                @if ($signedIn)
                    <a href="{{ url('/') }}" class="btn btn-primary btn-sm">{{ __('home.nav.open_app') }}</a>
                @else
                    <a href="{{ url('/login') }}" class="btn btn-quiet btn-sm hidden sm:inline-flex">{{ __('home.nav.sign_in') }}</a>
                    <a href="{{ url('/register') }}" class="btn btn-primary btn-sm">{{ __('home.nav.start') }}</a>
                @endif
            </div>
        </nav>
    </header>

    <main id="main">
        {{-- ── Hero ───────────────────────────────────────────── --}}
        <section class="mx-auto grid max-w-[1200px] items-center gap-14 px-5 pt-14 pb-20 sm:px-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)] lg:pt-24 lg:pb-28">
            <div>
                <p class="eyebrow rise" style="--i:0">{{ __('home.hero.eyebrow') }}</p>
                <h1 class="hero-title rise mt-5 font-display text-[clamp(44px,6.4vw,84px)] leading-[0.95] font-extrabold tracking-[-0.05em]" style="--i:1">
                    {{ __('home.hero.title') }}
                </h1>
                <p class="rise mt-7 max-w-xl text-[clamp(17px,1.6vw,20px)] leading-relaxed font-light text-ink-2" style="--i:2">
                    {{ __('home.hero.lead') }}
                </p>
                <div class="rise mt-9 flex flex-wrap items-center gap-3" style="--i:3">
                    <a href="{{ url($signedIn ? '/' : '/register') }}" class="btn btn-primary h-12 px-6 text-[15px]">
                        {{ $signedIn ? __('home.nav.open_app') : __('home.hero.primary') }}
                        <svg class="rtl:-scale-x-100" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </a>
                    @unless ($signedIn)
                        <a href="{{ url('/login') }}" class="btn btn-outline h-12 px-6 text-[15px]">{{ __('home.hero.secondary') }}</a>
                    @endunless
                </div>
                <p class="rise mt-4 text-[13px] text-ink-3" style="--i:4">{{ __('home.hero.note') }}</p>
            </div>

            {{-- Product shot: a conversation over a slice of the board. Static, so it works with reduced motion. --}}
            <figure class="relative" aria-label="{{ __('home.mock.label') }}">
                <div class="overflow-hidden rounded-2xl bg-[#0c1211] p-6 text-[#e9efed] ring-1 ring-black/5 sm:p-7">
                    <div class="mb-5 flex items-center gap-1.5" aria-hidden="true">
                        <span class="size-2.5 rounded-full bg-white/15"></span><span class="size-2.5 rounded-full bg-white/15"></span><span class="size-2.5 rounded-full bg-white/15"></span>
                    </div>
                    <ol class="space-y-3 text-[14px] leading-relaxed">
                        @foreach (__('home.mock.lines') as $i => $line)
                            <li class="rise flex gap-3" style="--i:{{ $i + 4 }}">
                                <span class="w-14 shrink-0 text-end font-mono text-[12px] leading-6 {{ $line['who'] === 'you' ? 'text-[#8a9692]' : ($line['who'] === 'tool' ? 'text-[#fb923c]' : 'text-[#2dd4bf]') }}">
                                    {{ $line['who'] === 'tool' ? '⏵' : __('home.mock.'.$line['who']) }}
                                </span>
                                @if ($line['who'] === 'tool')
                                    <code class="font-mono text-[13px] leading-6 text-[#fb923c]" dir="ltr">{{ $line['text'] }}</code>
                                @else
                                    <span class="text-[#dbe4e1]">{{ $line['text'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="relative -mt-6 ms-6 grid grid-cols-3 gap-2 rounded-2xl bg-surface p-3 ring-1 ring-line sm:ms-10">
                    @foreach (__('home.mock.columns') as $c => $column)
                        @php $card = __('home.mock.cards')[$c]; @endphp
                        <div class="min-w-0 rounded-xl p-2 {{ $c === 1 ? 'bg-signal-soft' : 'bg-sunken' }}">
                            <p class="truncate px-1 pb-2 text-[11px] font-bold {{ $c === 1 ? 'text-signal' : 'text-ink-2' }}">{{ $column }}</p>
                            <div class="rounded-lg bg-surface p-2.5 ring-1 ring-line">
                                <p class="task-key text-[11px]" dir="ltr">{{ $card['key'] }}</p>
                                <p class="mt-1 line-clamp-2 text-[12px] leading-snug font-semibold {{ $c === 2 ? 'text-ink-3 line-through' : 'text-ink' }}">{{ $card['title'] }}</p>
                                @if ($c === 1)
                                    <span class="mt-2 inline-flex rounded-full bg-signal-soft px-1.5 py-0.5 text-[10px] font-semibold text-signal">{{ __('home.mock.via') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </figure>
        </section>

        {{-- ── Works with ─────────────────────────────────────── --}}
        <section id="clients" class="scroll-mt-20 border-y border-line">
            <div class="mx-auto flex max-w-[1200px] flex-wrap items-center gap-x-10 gap-y-4 px-5 py-8 sm:px-8">
                <p class="eyebrow">{{ __('home.clients.title') }}</p>
                <ul class="flex flex-wrap items-center gap-x-9 gap-y-3 font-display text-[18px] font-bold tracking-[-0.02em] text-ink-2" dir="ltr">
                    <li>Claude.ai</li>
                    <li>Claude Desktop</li>
                    <li>Claude Code</li>
                    <li>ChatGPT</li>
                    <li>Cursor</li>
                    <li>VS Code</li>
                </ul>
                <p class="text-[14px] text-ink-3">+ {{ __('home.clients.any') }}</p>
            </div>
        </section>

        {{-- ── Features ───────────────────────────────────────── --}}
        <section id="features" class="mx-auto max-w-[1200px] scroll-mt-20 px-5 py-24 sm:px-8 lg:py-32">
            <div class="max-w-3xl" data-reveal>
                <p class="eyebrow">{{ __('home.features.eyebrow') }}</p>
                <h2 class="mt-4 text-[clamp(32px,4.4vw,56px)] leading-[1.02] font-extrabold tracking-[-0.045em]">{{ __('home.features.title') }}</h2>
            </div>

            <div class="mt-16 grid gap-x-14 gap-y-14 md:grid-cols-2 lg:grid-cols-3">
                @foreach (__('home.features.items') as $i => $feature)
                    <article data-reveal style="--d:{{ ($i % 3) * 80 }}ms" class="{{ $i === 1 ? 'feature-accent' : '' }}">
                        <h3 class="text-[22px] leading-tight font-bold tracking-[-0.025em] {{ $i === 1 ? 'text-signal' : '' }}">{{ $feature['title'] }}</h3>
                        <p class="mt-3 text-[16px] leading-relaxed text-ink-2">{{ $feature['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- ── How it works (a real three-step sequence) ──────── --}}
        <section id="how" class="scroll-mt-20 bg-sunken/70">
            <div class="mx-auto max-w-[1200px] px-5 py-24 sm:px-8 lg:py-32">
                <div class="max-w-3xl" data-reveal>
                    <p class="eyebrow">{{ __('home.how.eyebrow') }}</p>
                    <h2 class="mt-4 text-[clamp(32px,4.4vw,56px)] leading-[1.02] font-extrabold tracking-[-0.045em]">{{ __('home.how.title') }}</h2>
                </div>
                <ol class="mt-16 grid gap-12 md:grid-cols-3">
                    @foreach (__('home.how.steps') as $i => $step)
                        <li data-reveal style="--d:{{ $i * 90 }}ms">
                            <span class="font-display text-[56px] leading-none font-extrabold text-brand/25 tabular-nums" aria-hidden="true" dir="ltr">{{ $i + 1 }}</span>
                            <h3 class="mt-4 text-[22px] leading-tight font-bold tracking-[-0.025em]">{{ $step['title'] }}</h3>
                            <p class="mt-3 text-[16px] leading-relaxed text-ink-2">{{ $step['body'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- ── Prompts ────────────────────────────────────────── --}}
        <section class="mx-auto max-w-[1200px] px-5 py-24 sm:px-8 lg:py-32">
            <div class="grid gap-14 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
                <div data-reveal>
                    <p class="eyebrow">{{ __('home.prompts.eyebrow') }}</p>
                    <h2 class="mt-4 text-[clamp(32px,4.4vw,56px)] leading-[1.02] font-extrabold tracking-[-0.045em]">{{ __('home.prompts.title') }}</h2>
                </div>
                <ul class="divide-y divide-line border-y border-line">
                    @foreach (__('home.prompts.items') as $i => $prompt)
                        <li class="flex gap-4 py-6" data-reveal style="--d:{{ $i * 70 }}ms">
                            <span class="font-display text-[30px] leading-none text-brand" aria-hidden="true">{{ $rtl ? '«' : '“' }}</span>
                            <p class="text-[clamp(17px,1.7vw,21px)] leading-snug text-ink">{{ $prompt }}</p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- ── FAQ ────────────────────────────────────────────── --}}
        <section id="faq" class="scroll-mt-20 border-t border-line">
            <div class="mx-auto grid max-w-[1200px] gap-14 px-5 py-24 sm:px-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:py-32">
                <div data-reveal>
                    <p class="eyebrow">{{ __('home.faq.eyebrow') }}</p>
                    <h2 class="mt-4 text-[clamp(32px,4.4vw,56px)] leading-[1.02] font-extrabold tracking-[-0.045em]">{{ __('home.faq.title') }}</h2>
                </div>
                <div class="divide-y divide-line border-y border-line">
                    @foreach (__('home.faq.items') as $item)
                        <details class="faq group py-2" data-reveal>
                            <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-6 py-3 text-[18px] font-semibold text-ink">
                                {{ $item['q'] }}
                                <svg class="shrink-0 text-ink-3 transition-transform duration-300 ease-snap group-open:rotate-45" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                            </summary>
                            <p class="pb-5 text-[16px] leading-relaxed text-ink-2">{{ $item['a'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ── Closing CTA ────────────────────────────────────── --}}
        <section class="bg-[#0c1211] text-[#e9efed]">
            <div class="mx-auto max-w-[1200px] px-5 py-24 sm:px-8 lg:py-32" data-reveal>
                <h2 class="max-w-3xl font-display text-[clamp(36px,5.6vw,72px)] leading-[0.98] font-extrabold tracking-[-0.05em] text-[#f3f7f5]">{{ __('home.cta.title') }}</h2>
                <p class="mt-6 max-w-xl text-[18px] leading-relaxed font-light text-[#aab6b2]">{{ __('home.cta.lead') }}</p>
                <a href="{{ url($signedIn ? '/' : '/register') }}" class="btn mt-10 h-12 bg-[#2dd4bf] px-6 text-[15px] text-[#04211e] hover:bg-[#5eead4]">
                    {{ $signedIn ? __('home.nav.open_app') : __('home.cta.button') }}
                    <svg class="rtl:-scale-x-100" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </a>
            </div>
        </section>
    </main>

    {{-- ── Footer ─────────────────────────────────────────────── --}}
    <footer class="border-t border-line">
        <div class="mx-auto flex max-w-[1200px] flex-wrap items-center justify-between gap-6 px-5 py-10 text-[14px] text-ink-3 sm:px-8">
            <div class="flex items-center gap-3">
                <span class="wordmark font-display text-[20px] font-extrabold tracking-[-0.05em] text-ink" dir="ltr">Relay<span class="text-brand">.</span></span>
                <span>{{ __('home.footer.by') }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                <span>{{ __('home.footer.contact') }}: <a href="mailto:{{ config('relay.contact_email') }}" class="font-medium text-ink-2 hover:text-brand" dir="ltr">{{ config('relay.contact_email') }}</a></span>
                <a href="{{ url('/'.$other) }}" hreflang="{{ $other }}" lang="{{ $other }}" class="hover:text-ink">{{ __('home.nav.switch') }}</a>
                <span>© {{ date('Y') }} {{ $rtl ? 'تنامي' : 'Tanami' }}. {{ __('home.footer.rights') }}</span>
            </div>
        </div>
    </footer>
</body>
</html>
