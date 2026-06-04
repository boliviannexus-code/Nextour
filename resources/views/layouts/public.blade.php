<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    @foreach (config('locales.available') as $localeCode => $locale)
        <link rel="alternate" hreflang="{{ $localeCode }}" href="{{ url('/'.$localeCode.'/'.collect(request()->segments())->skip(1)->implode('/')) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ url('/'.config('locales.fallback').'/'.collect(request()->segments())->skip(1)->implode('/')) }}">
    <title>@yield('title', config('app.name', 'Tours'))</title>
    <meta name="description" content="@yield('meta_description', __('Tours, hospedajes y experiencias locales en Bolivia.'))">
    <meta property="og:title" content="@yield('og_title', trim($__env->yieldContent('title', config('app.name', 'Tours'))))">
    <meta property="og:description" content="@yield('og_description', trim($__env->yieldContent('meta_description', __('Tours, hospedajes y experiencias locales en Bolivia.'))))">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="public-body">
@php($publicWebsite = app(\App\Services\WebsiteContentService::class)->settings())
<header class="public-navbar">
    <nav class="container-xl d-flex align-items-center justify-content-between gap-3 py-3">
        <a class="public-brand" href="{{ route('public.home') }}" aria-label="{{ __('Ir al inicio') }}">
            @if ($publicWebsite->logo_url)
                <img class="public-brand-logo" src="{{ $publicWebsite->logo_url }}" alt="{{ config('app.name', 'Tours') }}">
            @else
                <span class="public-brand-mark"><i class="ti ti-map-pin-star"></i></span>
            @endif
            <span>{{ config('app.name', 'Tours') }}</span>
        </a>
        <div class="d-flex align-items-center gap-2 gap-md-3">
            <a class="public-nav-link d-none d-sm-inline-flex" href="{{ route('public.tours.index') }}">{{ __('Explorar tours') }}</a>
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle public-language-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ config('locales.available.'.app()->getLocale().'.flag') }} {{ config('locales.available.'.app()->getLocale().'.native') }}
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    @foreach (config('locales.available') as $localeCode => $locale)
                        <a class="dropdown-item {{ app()->getLocale() === $localeCode ? 'active' : '' }}" href="{{ url('/'.$localeCode.'/'.collect(request()->segments())->skip(1)->implode('/')) }}" hreflang="{{ $localeCode }}">
                            <span class="me-2">{{ $locale['flag'] }}</span>{{ $locale['native'] }}
                        </a>
                    @endforeach
                </div>
            </div>
            @auth
                @if (auth()->user()->hasRole('tourist'))
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('tourist.reservations.index') }}">{{ __('Mis reservas') }}</a>
                @else
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('dashboard') }}">Admin</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-link public-nav-link p-0" type="submit">{{ __('Cerrar sesión') }}</button>
                </form>
            @else
                <a class="public-nav-link" href="{{ route('login') }}">{{ __('Ingresar') }}</a>
                <a class="btn btn-primary btn-sm" href="{{ route('tourist.register') }}">{{ __('Crear cuenta') }}</a>
            @endauth
        </div>
    </nav>
</header>

<main>
    <x-admin.flash />
    @yield('content')
</main>

<footer class="public-footer">
    <div class="container-xl d-flex flex-column flex-md-row justify-content-between gap-3 py-4">
        <div>
            <strong>{{ config('app.name', 'Tours') }}</strong>
            <p class="mb-0 text-muted">{{ __('Experiencias locales, reservas claras y soporte cercano.') }}</p>
        </div>
        <div class="d-flex gap-3 text-muted">
            <span><i class="ti ti-shield-check me-1"></i>{{ __('Reserva segura') }}</span>
            <span><i class="ti ti-brand-whatsapp me-1"></i>WhatsApp</span>
        </div>
    </div>
</footer>

@if ($publicWebsite->popup_enabled && ($publicWebsite->popup_title || $publicWebsite->popup_body))
    <div class="public-popup" data-public-popup hidden>
        <div class="public-popup-dialog" role="dialog" aria-modal="true" aria-labelledby="public-popup-title">
            @if ($publicWebsite->popup_image_url)
                <img src="{{ $publicWebsite->popup_image_url }}" alt="{{ $publicWebsite->popup_title ?: 'Oferta destacada' }}">
            @endif
            <div class="public-popup-content">
                <button class="btn btn-icon btn-sm public-popup-close" type="button" data-public-popup-close aria-label="Cerrar oferta">
                    <i class="ti ti-x"></i>
                </button>
                @if ($publicWebsite->popup_title)
                    <h2 id="public-popup-title">{{ $publicWebsite->popup_title }}</h2>
                @endif
                @if ($publicWebsite->popup_body)
                    <p>{{ $publicWebsite->popup_body }}</p>
                @endif
                @if ($publicWebsite->popup_cta_label && $publicWebsite->popup_cta_url)
                    <a class="btn btn-primary" href="{{ $publicWebsite->popup_cta_url }}">{{ $publicWebsite->popup_cta_label }}</a>
                @endif
            </div>
        </div>
    </div>
@endif
@stack('scripts')
</body>
</html>
