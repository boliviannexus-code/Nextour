@extends('layouts.admin')

@section('title', 'Suscripciones y Creditos | '.config('app.name', 'Base Admin'))
@section('page-title', 'Suscripciones y Creditos')
@section('page-subtitle', 'Gestion global de limites gratuitos, creditos y reservas retenidas')

@section('content')
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-xl-7">
            <x-ui.form-panel :action="route('subscriptions.settings.update')" method="PUT" title="Configuracion Global">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="free_active_tours_limit">Tours gratuitos</label>
                        <input class="form-control @error('free_active_tours_limit') is-invalid @enderror" id="free_active_tours_limit" name="free_active_tours_limit" type="number" min="0" value="{{ old('free_active_tours_limit', $setting->free_active_tours_limit) }}">
                        @error('free_active_tours_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="free_daily_pax_limit">Limite diario</label>
                        <input class="form-control @error('free_daily_pax_limit') is-invalid @enderror" id="free_daily_pax_limit" name="free_daily_pax_limit" type="number" min="0" value="{{ old('free_daily_pax_limit', $setting->free_daily_pax_limit) }}">
                        @error('free_daily_pax_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="free_weekly_pax_limit">Limite semanal</label>
                        <input class="form-control @error('free_weekly_pax_limit') is-invalid @enderror" id="free_weekly_pax_limit" name="free_weekly_pax_limit" type="number" min="0" value="{{ old('free_weekly_pax_limit', $setting->free_weekly_pax_limit) }}">
                        @error('free_weekly_pax_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="credits_per_booking">Creditos por reserva</label>
                        <input class="form-control @error('credits_per_booking') is-invalid @enderror" id="credits_per_booking" name="credits_per_booking" type="number" min="1" value="{{ old('credits_per_booking', $setting->credits_per_booking) }}">
                        @error('credits_per_booking')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="credit_price">Precio credito USD</label>
                        <input class="form-control @error('credit_price') is-invalid @enderror" id="credit_price" name="credit_price" type="number" min="0" step="0.01" value="{{ old('credit_price', $setting->credit_price) }}">
                        @error('credit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Alertas</label>
                        @php($selectedThresholds = collect(old('warning_thresholds', $setting->warning_thresholds ?: [50, 75, 90, 100]))->map(fn ($threshold) => (int) $threshold)->all())
                        <div class="d-flex flex-wrap gap-2">
                            @foreach ([50, 75, 90, 100] as $threshold)
                                <label class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="checkbox" name="warning_thresholds[]" value="{{ $threshold }}" @checked(in_array($threshold, $selectedThresholds, true))>
                                    <span class="form-check-label">{{ $threshold }}%</span>
                                </label>
                            @endforeach
                        </div>
                        @error('warning_thresholds')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-device-floppy me-1"></i>Guardar configuracion
                        </button>
                    </div>
                </div>
            </x-ui.form-panel>
        </div>

        <div class="col-xl-5">
            <x-ui.form-panel :action="route('subscriptions.credits.store')" title="Agregar Creditos">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="company_id">Empresa</label>
                        <select class="form-select @error('company_id') is-invalid @enderror" id="company_id" name="company_id">
                            <option value="">Seleccionar empresa</option>
                            @foreach ($companies as $row)
                                <option value="{{ $row['company']->id }}" @selected((int) old('company_id') === $row['company']->id)>
                                    {{ $row['company']->name }} · {{ $row['status']['credits_balance'] }} creditos
                                </option>
                            @endforeach
                        </select>
                        @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="credits">Creditos</label>
                        <input class="form-control @error('credits') is-invalid @enderror" id="credits" name="credits" type="number" min="1" value="{{ old('credits', 1) }}">
                        @error('credits')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="reason">Motivo</label>
                        <input class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" value="{{ old('reason') }}" placeholder="Carga manual por soporte">
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success" type="submit">
                            <i class="ti ti-plus me-1"></i>Agregar y liberar retenidas
                        </button>
                    </div>
                </div>
            </x-ui.form-panel>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <x-ui.table-card title="Empresas">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th class="text-end">Tours activos</th>
                            <th class="text-end">Uso diario</th>
                            <th class="text-end">Uso semanal</th>
                            <th class="text-end">Creditos</th>
                            <th class="text-end">Retenidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $row['company']->name }}</div>
                                    <div class="text-body-secondary small">{{ $row['company']->email ?? 'Sin email' }}</div>
                                </td>
                                <td class="text-end">{{ $row['status']['published_tours'] }} / {{ $row['status']['tour_limit'] }}</td>
                                <td class="text-end">{{ $row['status']['daily_usage'] }} / {{ $row['status']['daily_limit'] }}</td>
                                <td class="text-end">{{ $row['status']['weekly_usage'] }} / {{ $row['status']['weekly_limit'] }}</td>
                                <td class="text-end">{{ $row['status']['credits_balance'] }}</td>
                                <td class="text-end">
                                    <span class="badge {{ $row['held_count'] > 0 ? 'text-bg-warning' : 'text-bg-secondary' }}">{{ $row['held_count'] }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="6">No hay empresas registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>

        <div class="col-xl-6">
            <x-ui.table-card title="Movimientos de Creditos">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Empresa</th>
                            <th>Tipo</th>
                            <th class="text-end">Creditos</th>
                            <th class="text-end">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td>{{ $movement->created_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $movement->company?->name ?? 'Sin empresa' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ str($movement->movement_type)->replace('_', ' ')->title() }}</div>
                                    <div class="text-body-secondary small">{{ $movement->reason }}</div>
                                </td>
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
                            <th>Empresa</th>
                            <th>Tour</th>
                            <th class="text-end">Pax</th>
                            <th class="text-end">Creditos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($heldBookings as $booking)
                            <tr>
                                <td>{{ $booking->travel_date?->format('Y-m-d') }}</td>
                                <td>{{ $booking->tour?->company?->name ?? 'Sin empresa' }}</td>
                                <td>{{ $booking->tour?->display_title ?? 'Tour eliminado' }}</td>
                                <td class="text-end">{{ $booking->people }}</td>
                                <td class="text-end">{{ $booking->credits_required ?: $setting->credits_per_booking }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="5">No hay reservas retenidas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>

        <div class="col-12">
            <x-ui.table-card title="Auditoria de Monetizacion">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Evento</th>
                            <th>Empresa</th>
                            <th>Reserva</th>
                            <th>Usuario</th>
                            <th>Descripcion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($auditLogs as $log)
                            <tr>
                                <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                                <td><span class="badge text-bg-info">{{ $log->event_label }}</span></td>
                                <td>{{ $log->company?->name ?? 'Global' }}</td>
                                <td>{{ $log->booking?->booking_code ?? '-' }}</td>
                                <td>{{ $log->creator?->name ?? 'Sistema' }}</td>
                                <td>{{ $log->description }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="6">Aun no hay auditoria de monetizacion.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>
    </div>
@endsection
