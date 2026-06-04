@extends('layouts.admin')

@section('title', 'Mi Suscripcion | '.config('app.name', 'Base Admin'))
@section('page-title', 'Mi Suscripcion')
@section('page-subtitle', 'Estado de limites gratuitos, creditos y reservas retenidas')

@section('content')
    <div class="row g-3">
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Creditos disponibles" :value="$status['credits_balance']" icon="ti ti-coins" tone="primary" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Tours publicados" value="{{ $status['published_tours'].' / '.$status['tour_limit'] }}" icon="ti ti-map-2" tone="success" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Uso diario" value="{{ $status['daily_usage'].' / '.$status['daily_limit'].' pax' }}" icon="ti ti-calendar" tone="warning" />
        </div>
        <div class="col-sm-6 col-xl-3">
            <x-ui.stat-card label="Uso 7 dias" value="{{ $status['weekly_usage'].' / '.$status['weekly_limit'].' pax' }}" icon="ti ti-calendar-stats" tone="info" />
        </div>
    </div>

    @if ($status['messages'])
        <div class="alert alert-warning mt-3">
            <div class="fw-semibold mb-1">Alertas preventivas</div>
            <ul class="mb-0 ps-3">
                @foreach ($status['messages'] as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mt-1">
        <div class="col-xl-6">
            <x-ui.table-card title="Historial de Creditos">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th class="text-end">Creditos</th>
                            <th class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ str($movement->movement_type)->replace('_', ' ')->title() }}</td>
                                <td>{{ $movement->reason }}</td>
                                <td class="text-end">{{ $movement->credits > 0 ? '+' : '' }}{{ $movement->credits }}</td>
                                <td class="text-end">{{ $movement->balance_after }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="5">Aun no hay movimientos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>

        <div class="col-xl-6">
            <x-ui.table-card title="Reservas Retenidas">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tour</th>
                            <th class="text-end">Pax</th>
                            <th class="text-end">Creditos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($heldBookings as $booking)
                            <tr>
                                <td>{{ $booking->travel_date?->format('Y-m-d') }}</td>
                                <td>{{ $booking->tour?->display_title ?? 'Tour eliminado' }}</td>
                                <td class="text-end">{{ $booking->people }}</td>
                                <td class="text-end">{{ $booking->credits_required ?: $setting->credits_per_booking }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="4">No hay reservas retenidas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>

        <div class="col-12">
            <x-ui.table-card title="Reservas Liberadas">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tour</th>
                            <th>Reserva</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($releasedLogs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $log->tour?->display_title ?? 'Tour eliminado' }}</td>
                                <td>{{ $log->booking?->booking_code ?? '-' }}</td>
                                <td>{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="4">No hay reservas liberadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>
    </div>
@endsection
