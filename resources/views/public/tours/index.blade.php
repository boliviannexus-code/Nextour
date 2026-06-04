@extends('layouts.public')

@section('title', __('Explorar tours').' | '.config('app.name', 'Tours'))

@section('content')
<section class="market-header">
    <div class="container-xl">
        <h1>{{ __('Encuentra tu próxima experiencia') }}</h1>
        <p>{{ __('Filtra por destino, rango de fechas y estilo de guía.') }}</p>
    </div>
</section>

<section class="public-section pt-4">
    <div class="container-xl marketplace-layout">
        <aside class="filter-panel">
            <form method="GET" action="{{ route('public.tours.index') }}">
                <h2>{{ __('Filtros') }}</h2>
                <label class="form-label" for="destination">{{ __('Destino') }}</label>
                <input type="hidden" name="destination" value="{{ $filters['destination'] ?? '' }}" data-location-city-value>
                <input class="form-control mb-3" id="destination" value="{{ $filters['destination'] ?? '' }}" placeholder="{{ __('Ciudad o destino') }}" data-location-city data-location-search-url="{{ route('locations.search') }}">

                <label class="form-label" for="start_date">{{ __('Fecha inicio') }}</label>
                <input class="form-control mb-3" id="start_date" name="start_date" type="date" value="{{ $filters['start_date'] ?? ($filters['date'] ?? '') }}">

                <label class="form-label" for="end_date">{{ __('Fecha fin') }}</label>
                <input class="form-control mb-3" id="end_date" name="end_date" type="date" value="{{ $filters['end_date'] ?? '' }}">

                <label class="form-label" for="people">{{ __('Personas') }}</label>
                <input class="form-control mb-3" id="people" name="people" type="number" min="1" value="{{ $filters['people'] ?? '' }}">

                <label class="form-label" for="category">{{ __('Categoría') }}</label>
                <select class="form-select mb-3" id="category" name="category">
                    <option value="">{{ __('Todas') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(($filters['category'] ?? '') == $category->id)>{{ $category->localized_name }}</option>
                    @endforeach
                </select>

                <label class="form-label" for="duration">{{ __('Duración') }}</label>
                <select class="form-select mb-4" id="duration" name="duration">
                    <option value="">{{ __('Cualquiera') }}</option>
                    <option value="short" @selected(($filters['duration'] ?? '') === 'short')>{{ __('Horas') }}</option>
                    <option value="full_day" @selected(($filters['duration'] ?? '') === 'full_day')>{{ __('Día completo') }}</option>
                    <option value="multi_day" @selected(($filters['duration'] ?? '') === 'multi_day')>{{ __('Varios días') }}</option>
                </select>

                <button class="btn btn-primary w-100" type="submit"><i class="ti ti-adjustments me-2"></i>{{ __('Aplicar filtros') }}</button>
                <a class="btn btn-link w-100 mt-2" href="{{ route('public.tours.index') }}">{{ __('Limpiar') }}</a>
            </form>
        </aside>

        <div class="market-results">
            <div class="results-toolbar">
                <div>
                    <strong>{{ __(':count tours encontrados', ['count' => $tours->total()]) }}</strong>
                    <span>{{ __('Disponibilidad sujeta a cupos por fecha.') }}</span>
                </div>
            </div>
            <div class="tour-grid">
                @forelse ($tours as $tour)
                    <x-public.tour-card :tour="$tour" />
                @empty
                    <div class="public-empty">{{ __('No encontramos tours con esos filtros. Prueba otra fecha o destino.') }}</div>
                @endforelse
            </div>
            <div class="mt-4">{{ $tours->links() }}</div>
        </div>
    </div>
</section>
@endsection
