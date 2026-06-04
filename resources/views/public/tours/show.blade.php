@extends('layouts.public')

@section('title', $tour->display_title.' | '.config('app.name', 'Tours'))

@section('content')
@php
    $mainImage = $tour->images->first()?->url;
    $price = $tour->prices->min('price_usd');
    $rating = $tour->reviews_avg_rating ? number_format((float) $tour->reviews_avg_rating, 1) : null;
    $requestedDate = request('date');
    $calendarStart = now()->startOfDay();
    $calendarEnd = now()->addMonthsNoOverflow(3)->startOfDay();
    $availabilityByDate = $tour->availabilities->keyBy(fn ($availability): string => $availability->date->toDateString());
    $calendarDays = collect(iterator_to_array(\Carbon\CarbonPeriod::create($calendarStart, $calendarEnd)))
        ->map(function ($date) use ($availabilityByDate, $tour, $calendarStart): array {
            $date = \Illuminate\Support\Carbon::instance($date);
            $availability = $availabilityByDate->get($date->toDateString());
            $capacity = $availability ? ($availability->capacity ?? $tour->capacity) : null;
            $bookedCount = (int) ($availability?->booked_count ?? 0);
            $availableSpots = $capacity === null ? null : max(0, (int) $capacity - $bookedCount);
            $isAvailable = $availability
                && $availability->status === \App\Models\TourAvailability::STATUS_AVAILABLE
                && ($capacity === null || $availableSpots > 0);

            return [
                'date' => $date->toDateString(),
                'day' => (int) $date->format('j'),
                'month_index' => (($date->year - $calendarStart->year) * 12) + ($date->month - $calendarStart->month),
                'month_label' => $date->translatedFormat('F Y'),
                'weekday' => (int) $date->dayOfWeekIso,
                'is_available' => (bool) $isAvailable,
                'spots' => $availableSpots,
                'spots_label' => $isAvailable
                    ? ($capacity === null ? __('Sin límite') : __(':count cupos', ['count' => $availableSpots]))
                    : '',
                'disabled_label' => $availability?->status === \App\Models\TourAvailability::STATUS_SOLD_OUT ? __('Sold out') : '',
                'status_label' => $availability?->status_label ?? __('No disponible'),
            ];
        })
        ->values();
    $selectableDates = $calendarDays->where('is_available', true)->pluck('date')->all();
    $selectedDate = in_array($requestedDate, $selectableDates, true) ? $requestedDate : null;
    $calendarPayload = [
        'days' => $calendarDays,
        'selectedDate' => $selectedDate,
        'monthIndex' => $selectedDate
            ? (int) ($calendarDays->firstWhere('date', $selectedDate)['month_index'] ?? 0)
            : 0,
        'labels' => [
            'previous' => __('Mes anterior'),
            'next' => __('Mes siguiente'),
            'selectDate' => __('Selecciona una fecha disponible'),
            'selected' => __('Fecha seleccionada'),
            'weekdays' => [__('Lun'), __('Mar'), __('Mié'), __('Jue'), __('Vie'), __('Sáb'), __('Dom')],
        ],
    ];
@endphp

<section class="tour-detail-hero">
    <div class="container-xl">
        <div class="tour-gallery">
            <div class="tour-gallery-main">
                @if ($mainImage)
                    <img src="{{ $mainImage }}" alt="{{ $tour->display_title }}">
                @else
                    <div class="tour-placeholder" role="img" aria-label="{{ __('Paisaje turístico de referencia') }}"><i class="ti ti-mountain"></i></div>
                @endif
            </div>
            @foreach ($tour->images->skip(1)->take(4) as $image)
                <img src="{{ $image->url }}" alt="{{ __('Imagen de :tour', ['tour' => $tour->display_title]) }}">
            @endforeach
        </div>
    </div>
</section>

<section class="public-section pt-4">
    <div class="container-xl detail-layout">
        <article class="detail-content">
            <div class="tour-overview-card">
                <div class="tour-kicker">
                    <span><i class="ti ti-map-pin"></i>{{ $tour->location_text ?: trim(($tour->city ? $tour->city.', ' : '').$tour->country) }}</span>
                    <span><i class="ti ti-star-filled text-warning"></i>{{ $rating ? __(':rating (:count opiniones)', ['rating' => $rating, 'count' => $tour->reviews_count]) : __('Nuevo') }}</span>
                </div>
                <div class="tour-title-row">
                    <div>
                        <h1>{{ $tour->display_title }}</h1>
                        <p class="lead">{{ $tour->localized_short_description }}</p>
                    </div>
                    <div class="tour-price-pill">
                        <span>{{ __('Desde') }}</span>
                        <strong>{{ $price ? '$'.number_format((float) $price, 2) : __('Consultar') }}</strong>
                        <small>{{ __('por persona') }}</small>
                    </div>
                </div>

                <div class="quick-facts">
                    <div><i class="ti ti-clock"></i><strong>{{ __('Duración') }}</strong><span>{{ $tour->duration ?: __('Por confirmar') }}</span></div>
                    <div><i class="ti ti-user-star"></i><strong>{{ __('Guía') }}</strong><span>{{ $tour->guideType->localized_title ?? __('Guía local') }}</span></div>
                    <div><i class="ti ti-map-2"></i><strong>{{ __('Ubicación') }}</strong><span>{{ $tour->city ?: $tour->country ?: __('Destino') }}</span></div>
                </div>
            </div>

            <section class="tour-detail-panel tour-description-panel">
                <h2>{{ __('Descripción completa') }}</h2>
                <div class="prose">{!! nl2br(e($tour->localized_description ?: __('El operador está completando la descripción de esta experiencia.'))) !!}</div>
            </section>

            <div class="tour-post-description-grid">
                <div class="tour-main-column">
                    <div class="tour-include-grid">
                        <section class="tour-detail-panel">
                            <h2>{{ __('Qué incluye') }}</h2>
                            <div class="prose">{!! nl2br(e($tour->translated('includes', fallback: $tour->includes ?: $tour->included) ?: __('Consulta los detalles incluidos antes de reservar.'))) !!}</div>
                        </section>
                        <section class="tour-detail-panel">
                            <h2>{{ __('Qué no incluye') }}</h2>
                            <div class="prose">{!! nl2br(e($tour->translated('excludes', fallback: $tour->excludes ?: $tour->not_included) ?: __('Gastos personales y servicios no mencionados.'))) !!}</div>
                        </section>
                    </div>

                    <section class="tour-detail-panel">
                        <h2>{{ __('Itinerario') }}</h2>
                        @forelse ($tour->itineraryDays as $day)
                            <div class="itinerary-day">
                                <strong>{{ __('Día :number', ['number' => $day->day_number]) }}: {{ $day->title }}</strong>
                                @if ($day->summary)<p>{{ $day->summary }}</p>@endif
                                @foreach ($day->stops as $stop)
                                    <div class="itinerary-stop">
                                        <span>{{ $stop->start_time ? \Illuminate\Support\Carbon::parse($stop->start_time)->format('H:i') : __('Horario flexible') }}</span>
                                        <div>{{ $stop->title }} @if($stop->location_name)<small>{{ $stop->location_name }}</small>@endif</div>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="public-empty compact">{{ __('El itinerario se confirmará con el operador del tour.') }}</div>
                        @endforelse
                    </section>

                    <div class="tour-secondary-grid">
                        <section class="tour-detail-panel">
                            <h2>{{ __('Punto de encuentro') }}</h2>
                            <p>{{ $tour->meeting_point ?: __('El punto de encuentro se coordinará en la confirmación.') }}</p>
                        </section>

                        <section class="tour-detail-panel">
                            <h2>{{ __('Políticas de cancelación') }}</h2>
                            <p>{{ __('Cancelación gratuita hasta 24 horas antes del inicio, salvo condiciones especiales indicadas por el operador.') }}</p>
                        </section>
                    </div>

                    <section class="tour-detail-panel">
                        <h2>{{ __('Opiniones de clientes') }}</h2>
                        @forelse ($tour->reviews as $review)
                            <div class="review-item">
                                <strong>{{ $review->title ?: $review->user->name }}</strong>
                                <span>{{ str_repeat('★', $review->rating) }}</span>
                                <p>{{ $review->comment }}</p>
                            </div>
                        @empty
                            <div class="public-empty compact">{{ __('Este tour aún no tiene opiniones. Puedes ser de los primeros en vivirlo.') }}</div>
                        @endforelse
                    </section>
                </div>

                <aside class="booking-box tour-reservation-card">
                    <div class="reservation-card-header">
                        <div>
                            <span class="text-muted">{{ __('Desde') }}</span>
                            <div class="booking-price">{{ $price ? '$'.number_format((float) $price, 2) : __('Consultar') }} <small>{{ __('por persona') }}</small></div>
                        </div>
                        <i class="ti ti-calendar-check"></i>
                    </div>
                    @if ($tour->minimum_capacity || $tour->capacity)
                        <div class="reservation-capacity-note">
                            @if ($tour->minimum_capacity)
                                {{ __('Mínimo :count para iniciar', ['count' => $tour->minimum_capacity]) }}
                            @endif
                            @if ($tour->minimum_capacity && $tour->capacity)
                                /
                            @endif
                            @if ($tour->capacity)
                                {{ __('Máximo :count cupos', ['count' => $tour->capacity]) }}
                            @endif
                        </div>
                    @endif
                    <form action="{{ route('public.bookings.create', $tour) }}" method="GET" data-tour-calendar-form>
                        <label class="form-label" for="tour-calendar-date">{{ __('Fecha') }}</label>
                        <input id="tour-calendar-date" name="date" type="hidden" value="{{ $selectedDate }}" data-calendar-date required>
                        <div class="tour-availability-calendar mb-3" data-tour-calendar='@json($calendarPayload)'>
                            <div class="tour-calendar-header">
                                <button class="btn btn-outline-secondary btn-sm btn-icon" type="button" data-calendar-prev aria-label="{{ __('Mes anterior') }}">
                                    <i class="ti ti-chevron-left"></i>
                                </button>
                                <div>
                                    <div class="tour-calendar-title" data-calendar-title></div>
                                    <div class="tour-calendar-hint" data-calendar-hint>{{ __('Selecciona una fecha disponible') }}</div>
                                </div>
                                <button class="btn btn-outline-secondary btn-sm btn-icon" type="button" data-calendar-next aria-label="{{ __('Mes siguiente') }}">
                                    <i class="ti ti-chevron-right"></i>
                                </button>
                            </div>
                            <div class="tour-calendar-weekdays" data-calendar-weekdays></div>
                            <div class="tour-calendar-grid" data-calendar-grid></div>
                        </div>
                        <label class="form-label" for="people">{{ __('Personas') }}</label>
                        <input class="form-control mb-3" id="people" name="people" type="number" min="1" value="2" required>
                        <button class="btn btn-primary w-100 btn-lg" type="submit" data-calendar-submit @disabled(! $selectedDate)>{{ __('Reservar ahora') }}</button>
                    </form>
                </aside>
            </div>
        </article>
    </div>
</section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-tour-calendar]').forEach((calendar) => {
                const form = calendar.closest('[data-tour-calendar-form]');
                const input = form?.querySelector('[data-calendar-date]');
                const submit = form?.querySelector('[data-calendar-submit]');
                const title = calendar.querySelector('[data-calendar-title]');
                const hint = calendar.querySelector('[data-calendar-hint]');
                const weekdays = calendar.querySelector('[data-calendar-weekdays]');
                const grid = calendar.querySelector('[data-calendar-grid]');
                const previous = calendar.querySelector('[data-calendar-prev]');
                const next = calendar.querySelector('[data-calendar-next]');
                const state = JSON.parse(calendar.dataset.tourCalendar || '{}');
                const days = state.days || [];
                const labels = state.labels || {};
                const monthCount = Math.max(1, ...days.map((day) => Number(day.month_index) + 1));
                let monthIndex = Math.min(Math.max(Number(state.monthIndex || 0), 0), monthCount - 1);
                let selectedDate = state.selectedDate || '';

                const render = () => {
                    const monthDays = days.filter((day) => Number(day.month_index) === monthIndex);
                    const firstDay = monthDays[0];

                    if (!firstDay || !grid || !title || !weekdays) {
                        return;
                    }

                    title.textContent = firstDay.month_label;
                    weekdays.innerHTML = (labels.weekdays || []).map((weekday) => `<span>${weekday}</span>`).join('');
                    grid.innerHTML = '';

                    const offset = Math.max(0, Number(firstDay.weekday || 1) - 1);
                    for (let index = 0; index < offset; index += 1) {
                        grid.insertAdjacentHTML('beforeend', '<span class="tour-calendar-empty"></span>');
                    }

                    monthDays.forEach((day) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = `tour-calendar-day ${day.is_available ? 'is-available' : 'is-disabled'} ${selectedDate === day.date ? 'is-selected' : ''}`;
                        button.disabled = !day.is_available;
                        button.dataset.date = day.date;
                        button.setAttribute('aria-label', `${day.date} - ${day.status_label}${day.spots_label ? ` - ${day.spots_label}` : ''}`);
                        button.innerHTML = `<strong>${day.day}</strong>${day.is_available ? `<span>${day.spots_label}</span>` : (day.disabled_label ? `<span>${day.disabled_label}</span>` : '')}`;
                        grid.append(button);
                    });

                    previous.disabled = monthIndex === 0;
                    next.disabled = monthIndex >= monthCount - 1;

                    if (input) {
                        input.value = selectedDate;
                    }

                    if (submit) {
                        submit.disabled = !selectedDate;
                    }

                    if (hint) {
                        hint.textContent = selectedDate ? `${labels.selected || 'Fecha seleccionada'}: ${selectedDate}` : (labels.selectDate || 'Selecciona una fecha disponible');
                    }
                };

                grid?.addEventListener('click', (event) => {
                    const button = event.target.closest('.tour-calendar-day.is-available');

                    if (!button) {
                        return;
                    }

                    selectedDate = button.dataset.date || '';
                    render();
                });

                previous?.addEventListener('click', () => {
                    monthIndex = Math.max(0, monthIndex - 1);
                    render();
                });

                next?.addEventListener('click', () => {
                    monthIndex = Math.min(monthCount - 1, monthIndex + 1);
                    render();
                });

                render();
            });
        });
    </script>
@endpush
