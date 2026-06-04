@extends('layouts.public')

@section('title', __('Tours y experiencias').' | '.config('app.name', 'Tours'))

@section('content')
<section class="hero-tourism" @if($websiteSettings->hero_image_url) style="background-image: linear-gradient(180deg, rgba(6, 31, 29, .18), rgba(6, 31, 29, .82)), url('{{ $websiteSettings->hero_image_url }}')" @endif>
    <div class="container-xl hero-tourism-inner">
        <div class="hero-copy">
            <span class="eyebrow">{{ $websiteSettings->hero_eyebrow ?: __('Experiencias locales verificadas') }}</span>
            <h1>{{ $websiteSettings->hero_title ?: __('Reserva tours memorables con guías locales') }}</h1>
            <p>{{ $websiteSettings->hero_subtitle ?: __('Encuentra disponibilidad real, compara experiencias y confirma tu próxima aventura en pocos pasos.') }}</p>
        </div>
        <form class="hero-search" action="{{ route('public.tours.index') }}" method="GET">
            <label>
                <span>{{ __('Destino') }}</span>
                <input type="hidden" name="destination" value="{{ $search['destination'] ?? '' }}" data-location-city-value>
                <input class="form-control" value="{{ $search['destination'] ?? '' }}" placeholder="Uyuni, La Paz, Cusco" data-location-city data-location-search-url="{{ route('locations.search') }}">
            </label>
            <label>
                <span>{{ __('Fecha inicio') }}</span>
                <input class="form-control" name="start_date" type="date" value="{{ $search['start_date'] ?? ($search['date'] ?? '') }}">
            </label>
            <label>
                <span>{{ __('Fecha fin') }}</span>
                <input class="form-control" name="end_date" type="date" value="{{ $search['end_date'] ?? '' }}">
            </label>
            <label>
                <span>{{ __('Personas') }}</span>
                <input class="form-control" name="people" type="number" min="1" value="{{ $search['people'] ?? 2 }}">
            </label>
            <button class="btn btn-primary btn-lg" type="submit"><i class="ti ti-search me-2"></i>{{ __('Buscar') }}</button>
        </form>
    </div>
</section>

@if ($companies->isNotEmpty())
    <section class="public-section public-companies">
        <div class="container-xl">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">{{ __('Operadores') }}</span>
                    <h2>{{ __('Empresas con experiencias publicadas') }}</h2>
                </div>
            </div>
            <div class="company-strip">
                @foreach ($companies as $company)
                    <div class="company-tile">
                        @if ($company->logo_url)
                            <img src="{{ $company->logo_url }}" alt="{{ $company->name }}">
                        @else
                            <span class="avatar bg-primary-lt text-primary">{{ str($company->name)->substr(0, 1)->upper() }}</span>
                        @endif
                        <div>
                            <strong>{{ $company->name }}</strong>
                    <span>{{ __(':count tours', ['count' => $company->tours_count]) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

<section class="public-section">
    <div class="container-xl">
        <div class="section-heading">
            <div>
                <span class="eyebrow">{{ __('Seleccionados') }}</span>
                <h2>{{ __('Tours destacados') }}</h2>
            </div>
            <a href="{{ route('public.tours.index') }}">{{ __('Ver todos') }}</a>
        </div>
        <div class="tour-grid">
            @forelse ($featuredTours as $tour)
                <x-public.tour-card :tour="$tour" />
            @empty
                <div class="public-empty">{{ __('Aún no hay tours publicados para reservar.') }}</div>
            @endforelse
        </div>
    </div>
</section>

<section class="public-section public-band">
    <div class="container-xl">
        <div class="section-heading">
            <div>
                <span class="eyebrow">{{ __('Explora por interés') }}</span>
                <h2>{{ __('Categorías de tours') }}</h2>
            </div>
        </div>
        <div class="category-grid">
            @foreach ($categories as $category)
                <a class="category-tile" href="{{ route('public.tours.index', ['category' => $category->id]) }}">
                    <i class="ti ti-compass"></i>
                    <strong>{{ $category->localized_name }}</strong>
                    <span>{{ __(':count experiencias', ['count' => $category->tours_count]) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="public-section">
    <div class="container-xl benefits-grid">
        <div><i class="ti ti-shield-check"></i><strong>{{ __('Reserva segura') }}</strong><span>{{ __('Datos claros antes de confirmar.') }}</span></div>
        <div><i class="ti ti-users"></i><strong>{{ __('Guías locales') }}</strong><span>{{ __('Experiencias operadas por especialistas del destino.') }}</span></div>
        <div><i class="ti ti-brand-whatsapp"></i><strong>{{ __('Soporte WhatsApp') }}</strong><span>{{ __('Acompañamiento antes y durante tu tour.') }}</span></div>
    </div>
</section>
@endsection
