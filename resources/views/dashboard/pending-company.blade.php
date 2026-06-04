@extends('layouts.admin')

@section('title', 'Estado de empresa | '.config('app.name', 'Base Admin'))
@section('page-title', 'Estado de empresa')
@section('page-subtitle', $company?->name ?? 'Solicitud empresarial')

@section('content')
    @php($isRejected = $registrationRequest?->status === \App\Models\RegistrationRequest::STATUS_REJECTED)

    <div class="alert {{ $isRejected ? 'alert-danger' : 'alert-warning' }}">
        <div class="d-flex gap-3">
            <span class="avatar {{ $isRejected ? 'bg-danger-lt text-danger' : 'bg-warning-lt text-warning' }}"><i class="ti ti-alert-circle"></i></span>
            <div>
                <div class="fw-semibold">{{ $isRejected ? 'Su solicitud fue rechazada.' : 'Su empresa aun no ha sido aprobada.' }}</div>
                <div>{{ $isRejected ? 'La solicitud fue cerrada por la administracion y ya no puede ser editada.' : 'Complete o corrija la informacion solicitada para continuar con el proceso.' }}</div>
            </div>
        </div>
    </div>

    @if ($isRejected && $registrationRequest?->rejection_reason)
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Motivo de rechazo</div>
            <div>{{ $registrationRequest->rejection_reason }}</div>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <x-ui.card title="Resumen">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Estado actual</dt>
                        <dd class="col-sm-7">
                            @if ($registrationRequest)
                                <span class="badge text-bg-{{ $registrationRequest->status_badge }}">{{ $registrationRequest->status_label }}</span>
                            @else
                                <span class="badge text-bg-secondary">PENDIENTE</span>
                            @endif
                        </dd>
                        <dt class="col-sm-5">Fecha de registro</dt>
                        <dd class="col-sm-7">{{ $company?->created_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                        <dt class="col-sm-5">Ultima revision</dt>
                        <dd class="col-sm-7">{{ $company?->last_reviewed_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                        <dt class="col-sm-5">Empresa</dt>
                        <dd class="col-sm-7">{{ $company?->name ?? '-' }}</dd>
                        <dt class="col-sm-5">Correo</dt>
                        <dd class="col-sm-7">{{ $company?->email ?? auth()->user()?->email }}</dd>
                    </dl>

                    <div class="d-grid gap-2 mt-4">
                        @if ($company && ! $isRejected)
                            <a class="btn btn-outline-primary" href="{{ route('companies.edit', $company) }}">
                                <i class="ti ti-edit me-2"></i>Editar informacion
                            </a>
                        @endif

                        @if ($registrationRequest && in_array($registrationRequest->status, [\App\Models\RegistrationRequest::STATUS_PENDING, \App\Models\RegistrationRequest::STATUS_OBSERVED], true))
                            <form method="POST" action="{{ route('registration-requests.resubmit', $registrationRequest) }}" data-confirm-delete="Enviar solicitud para revision?">
                                @csrf
                                <button class="btn btn-primary w-100" type="submit">
                                    <i class="ti ti-send me-2"></i>Reenviar solicitud
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-lg-7">
            <x-ui.table-card title="Observaciones">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Comentario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($registrationRequest?->reviews ?? collect())->filter(fn ($review) => in_array($review->status_after, [\App\Models\RegistrationRequest::STATUS_OBSERVED, \App\Models\RegistrationRequest::STATUS_REJECTED], true)) as $review)
                            <tr>
                                <td>{{ $review->reviewed_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $review->reviewer?->name ?? 'Administracion' }}</td>
                                <td>{{ $review->observation }}</td>
                            </tr>
                        @empty
                            <x-ui.empty-row colspan="3" message="Aun no hay observaciones." />
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>

            <x-ui.table-card title="Historial de revisiones">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Antes</th>
                            <th>Despues</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($registrationRequest?->reviews ?? [] as $review)
                            <tr>
                                <td>{{ $review->reviewed_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $review->status_before ? str($review->status_before)->replace('_', ' ')->upper() : '-' }}</td>
                                <td>{{ str($review->status_after)->replace('_', ' ')->upper() }}</td>
                                <td>{{ $review->reviewer?->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <x-ui.empty-row colspan="4" message="Sin historial registrado." />
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>
    </div>
@endsection
