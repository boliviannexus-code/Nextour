@extends('layouts.admin')

@section('title', 'Dashboard super admin | '.config('app.name', 'Base Admin'))
@section('page-title', 'Dashboard super admin')
@section('page-subtitle', 'Indicadores principales de empresas, tours, reservas e ingresos')

@section('content')
    <div class="dashboard-hero mb-3">
        <div class="dashboard-company">
            @if ($dashboardCompany?->logo_url)
                <img class="dashboard-company-logo" src="{{ $dashboardCompany->logo_url }}" alt="{{ $dashboardCompany->name }}">
            @else
                <span class="dashboard-company-mark">{{ str($dashboardCompany?->name ?? config('app.name', 'BA'))->substr(0, 2)->upper() }}</span>
            @endif
            <div>
                <div class="text-body-secondary small">Operacion turistica</div>
                <h2 class="mb-1">{{ config('app.name', 'Base Admin') }}</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="badge text-bg-primary">{{ $approvedCompanies }} empresas aprobadas</span>
                    <span class="badge text-bg-info">{{ $bookableTours }} tours reservables</span>
                    <span class="badge text-bg-warning">{{ $pendingRegistrationRequests }} solicitudes por revisar</span>
                    <span class="text-body-secondary small">{{ now()->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Empresas" :value="$totalCompanies" icon="ti ti-building-store" tone="primary" />
            <div class="dashboard-stat-note">{{ $activeCompanies }} activas · {{ $approvedCompanies }} aprobadas</div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Tours" :value="$totalTours" icon="ti ti-map-2" tone="success" />
            <div class="dashboard-stat-note">{{ $activeTours }} activos · {{ $pendingReviewTours }} en revision</div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Turistas" :value="number_format($totalTourists)" icon="ti ti-users-group" tone="warning" />
            <div class="dashboard-stat-note">{{ number_format($monthlyTourists) }} registrados este mes</div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Ingresos USD" :value="'$'.number_format($totalRevenue, 2)" icon="ti ti-cash" tone="info" />
            <div class="dashboard-stat-note">${{ number_format($monthlyRevenue, 2) }} este mes</div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <x-ui.table-card title="Tours con mayor movimiento">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Tour</th>
                            <th>Empresa</th>
                            <th class="text-end">Reservas</th>
                            <th class="text-end">Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topTours as $tour)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $tour->display_title ?: 'Tour sin titulo' }}</div>
                                    <div class="text-body-secondary small">{{ $tour->status_label }} · {{ $tour->review_status_label }}</div>
                                </td>
                                <td>{{ $tour->company?->name ?? 'Sin empresa' }}</td>
                                <td class="text-end">{{ number_format($tour->bookings_count) }}</td>
                                <td class="text-end">${{ number_format($tour->revenue_usd ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="4">Sin tours con reservas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>

        <div class="col-lg-5">
            <x-ui.card title="Pendientes relevantes">
                <div class="card-body">
                    <div class="dashboard-alert-row">
                        <span class="avatar avatar-sm bg-primary-lt text-primary"><i class="ti ti-building-plus"></i></span>
                        <div>
                            <div class="fw-semibold">{{ $pendingRegistrationRequests }} solicitudes de empresas</div>
                            <div class="text-body-secondary small">Pendientes, en revision u observadas.</div>
                        </div>
                    </div>
                    <div class="dashboard-alert-row">
                        <span class="avatar avatar-sm bg-success-lt text-success"><i class="ti ti-map-search"></i></span>
                        <div>
                            <div class="fw-semibold">{{ $pendingReviewTours }} tours por revisar</div>
                            <div class="text-body-secondary small">Contenido esperando aprobacion comercial.</div>
                        </div>
                    </div>
                    <div class="dashboard-alert-row">
                        <span class="avatar avatar-sm bg-warning-lt text-warning"><i class="ti ti-credit-card-off"></i></span>
                        <div>
                            <div class="fw-semibold">{{ $heldBookingsCount }} reservas retenidas</div>
                            <div class="text-body-secondary small">{{ $pendingCreditPurchaseRequests }} compras de creditos pendientes.</div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-lg-7">
            <x-ui.table-card title="Reservas recientes">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Turista</th>
                            <th>Tour</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestBookings as $booking)
                            <tr>
                                <td><span class="badge text-bg-secondary">{{ $booking->booking_code }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ trim($booking->first_name.' '.$booking->last_name) }}</div>
                                    <div class="text-body-secondary small">{{ $booking->people }} personas · {{ $booking->status_label }}</div>
                                </td>
                                <td>
                                    <div>{{ $booking->tour?->display_title ?? 'Tour no disponible' }}</div>
                                    <div class="text-body-secondary small">{{ $booking->tour?->company?->name ?? 'Sin empresa' }}</div>
                                </td>
                                <td class="text-end">${{ number_format($booking->total_usd, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="4">Sin reservas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>

        <div class="col-lg-5">
            <x-ui.table-card title="Empresas por atender">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Estado</th>
                            <th class="text-end">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companiesNeedingAttention as $company)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $company->name }}</div>
                                    <div class="text-body-secondary small">{{ $company->email ?: 'Sin correo' }}</div>
                                </td>
                                <td><span class="badge text-bg-warning">{{ str($company->approval_status)->headline() }}</span></td>
                                <td class="text-end">{{ $company->created_at?->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="3">No hay empresas pendientes.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>
    </div>
@endsection
