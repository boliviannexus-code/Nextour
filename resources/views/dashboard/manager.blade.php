@extends('layouts.admin')

@section('title', 'Dashboard gerente | '.config('app.name', 'Base Admin'))
@section('page-title', 'Dashboard gerente')
@section('page-subtitle', $dashboardCompany ? 'Gestion de '.$dashboardCompany->name : 'Panel operativo de turismo')

@section('content')
    <div class="dashboard-hero mb-3">
        <div class="dashboard-company">
            @if ($dashboardCompany?->logo_url)
                <img class="dashboard-company-logo" src="{{ $dashboardCompany->logo_url }}" alt="{{ $dashboardCompany->name }}">
            @else
                <span class="dashboard-company-mark">{{ str($dashboardCompany?->name ?? config('app.name', 'TG'))->substr(0, 2)->upper() }}</span>
            @endif
            <div>
                <div class="text-body-secondary small">Panel operativo</div>
                <h2 class="mb-1">{{ $dashboardCompany?->name ?? 'Gestion turistica' }}</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge text-bg-primary">Tours</span>
                    <span class="badge text-bg-success">Reservas</span>
                    <span class="badge text-bg-info">Catalogo</span>
                    <span class="text-body-secondary small">{{ now()->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Tours publicados" :value="$activeTours" icon="ti ti-map-2" tone="primary" />
            <div class="dashboard-stat-note">{{ $totalTours }} tours registrados</div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Reservas confirmadas" :value="$confirmedBookings" icon="ti ti-ticket" tone="success" />
            <div class="dashboard-stat-note">{{ $totalBookings }} reservas totales</div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Ingresos del mes" value="${{ number_format((float) $monthlyRevenue, 2) }}" icon="ti ti-cash" tone="warning" />
            <div class="dashboard-stat-note">Segun reservas registradas</div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Pendientes de revision" :value="$pendingReviewTours" icon="ti ti-clipboard-check" tone="info" />
            <div class="dashboard-stat-note">{{ $categoriesCount }} categorias activas</div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-8">
            @if ($subscriptionStatus)
                <x-ui.card title="Estado de Suscripción">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="text-body-secondary small">Tours publicados</div>
                                <div class="h3 mb-1">{{ $subscriptionStatus['published_tours'] }} / {{ $subscriptionStatus['tour_limit'] }}</div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar" style="width: {{ $subscriptionStatus['tour_limit'] > 0 ? min(100, ($subscriptionStatus['published_tours'] / $subscriptionStatus['tour_limit']) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-body-secondary small">Creditos disponibles</div>
                                <div class="h3 mb-1">{{ $subscriptionStatus['credits_balance'] }}</div>
                                <div class="text-body-secondary small">{{ $subscriptionStatus['credits_per_booking'] }} credito(s) por reserva fuera de limite</div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-body-secondary small">Uso diario</div>
                                <div class="h3 mb-1">{{ $subscriptionStatus['daily_usage'] }} / {{ $subscriptionStatus['daily_limit'] }} pax</div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-success" style="width: {{ $subscriptionStatus['daily_limit'] > 0 ? min(100, ($subscriptionStatus['daily_usage'] / $subscriptionStatus['daily_limit']) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-body-secondary small">Uso ultimos 7 dias</div>
                                <div class="h3 mb-1">{{ $subscriptionStatus['weekly_usage'] }} / {{ $subscriptionStatus['weekly_limit'] }} pax</div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-info" style="width: {{ $subscriptionStatus['weekly_limit'] > 0 ? min(100, ($subscriptionStatus['weekly_usage'] / $subscriptionStatus['weekly_limit']) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>

                        @if ($subscriptionStatus['messages'])
                            <div class="alert alert-warning mt-3 mb-0">
                                <div class="fw-semibold mb-1">Alertas preventivas</div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($subscriptionStatus['messages'] as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <div class="text-body-secondary small mt-3">Sin alertas preventivas por ahora.</div>
                        @endif
                    </div>
                </x-ui.card>
            @endif

            <x-ui.table-card title="Reservas recientes">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Tour</th>
                            <th>Cliente</th>
                            <th>Fecha</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestBookings as $booking)
                            <tr>
                                <td><span class="badge text-bg-secondary">{{ $booking->booking_code }}</span></td>
                                <td>{{ $booking->tour?->display_title ?? 'Tour eliminado' }}</td>
                                <td>{{ $booking->first_name }} {{ $booking->last_name }}</td>
                                <td>{{ $booking->travel_date?->format('Y-m-d') }}</td>
                                <td class="text-end">${{ number_format((float) $booking->total_usd, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="5">Aun no hay reservas recientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>

            <x-ui.table-card title="Historial de Creditos">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th class="text-end">Creditos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($creditMovements as $movement)
                            <tr>
                                <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ str($movement->movement_type)->replace('_', ' ')->title() }}</td>
                                <td>{{ $movement->reason }}</td>
                                <td class="text-end">{{ $movement->credits > 0 ? '+' : '' }}{{ $movement->credits }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="4">Aun no hay movimientos de creditos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>

        <div class="col-lg-4">
            <x-ui.card title="Reservas retenidas">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <div class="text-body-secondary small">Pendientes por creditos</div>
                            <div class="h2 mb-0">{{ $heldBookingsCount }}</div>
                        </div>
                        <span class="avatar bg-warning-lt text-warning"><i class="ti ti-lock"></i></span>
                    </div>

                    <div class="list-group list-group-flush">
                        @forelse ($heldBookings as $booking)
                            <div class="list-group-item px-0">
                                <div class="fw-semibold">{{ $booking->tour?->display_title ?? 'Tour eliminado' }}</div>
                                <div class="text-body-secondary small">
                                    {{ $booking->travel_date?->format('Y-m-d') }} · {{ $booking->people }} pax · {{ $booking->credits_required ?: ($subscriptionStatus['credits_per_booking'] ?? 1) }} credito(s)
                                </div>
                            </div>
                        @empty
                            <div class="text-body-secondary small">No hay reservas retenidas.</div>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Reservas liberadas">
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @forelse ($releasedBookings as $log)
                            <div class="list-group-item px-0">
                                <div class="fw-semibold">{{ $log->tour?->display_title ?? 'Tour eliminado' }}</div>
                                <div class="text-body-secondary small">
                                    {{ $log->created_at?->format('Y-m-d H:i') }} · {{ $log->booking?->travel_date?->format('Y-m-d') ?? '-' }}
                                </div>
                            </div>
                        @empty
                            <div class="text-body-secondary small">No hay reservas liberadas.</div>
                        @endforelse
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card title="Accesos rapidos">
                <div class="card-body d-grid gap-2">
                    @can('tours.view')
                        <a class="btn btn-outline-primary text-start" href="{{ route('tours.index') }}"><i class="ti ti-map-2 me-2"></i>Gestionar tours</a>
                    @endcan
                    @can('tours.availability')
                        <a class="btn btn-outline-primary text-start" href="{{ route('tours.availability.index') }}"><i class="ti ti-calendar-stats me-2"></i>Disponibilidad</a>
                    @endcan
                    @can('bookings.view')
                        <a class="btn btn-outline-primary text-start" href="{{ route('bookings.index') }}"><i class="ti ti-ticket me-2"></i>Reservas</a>
                    @endcan
                    @can('website.manage')
                        <a class="btn btn-outline-primary text-start" href="{{ route('website-settings.edit') }}"><i class="ti ti-world-cog me-2"></i>Pagina web</a>
                    @endcan
                </div>
            </x-ui.card>
        </div>
    </div>
@endsection
