@extends('layouts.admin')

@section('title', 'Solicitud #'.$registrationRequest->id.' | '.config('app.name', 'Base Admin'))
@section('page-title', 'Solicitud #'.$registrationRequest->id)
@section('page-subtitle', $registrationRequest->type === \App\Models\RegistrationRequest::TYPE_COMPANY ? 'Empresa' : 'Independiente')

@section('content')
    <div class="row g-3">
        <div class="col-lg-8">
            <x-ui.card title="Detalle de solicitud">
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Estado</dt>
                        <dd class="col-sm-8"><span class="badge text-bg-{{ $registrationRequest->status_badge }}">{{ $registrationRequest->status_label }}</span></dd>
                        <dt class="col-sm-4">Usuario</dt>
                        <dd class="col-sm-8">{{ $registrationRequest->user->name }} · {{ $registrationRequest->user->email }}</dd>
                        <dt class="col-sm-4">Fecha de registro</dt>
                        <dd class="col-sm-8">{{ $registrationRequest->created_at?->format('Y-m-d H:i') }}</dd>

                        @if ($registrationRequest->type === \App\Models\RegistrationRequest::TYPE_COMPANY && $registrationRequest->company)
                            <dt class="col-sm-4">Nombre comercial</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->company->name }}</dd>
                            <dt class="col-sm-4">Razon social</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->company->legal_name }}</dd>
                            <dt class="col-sm-4">NIT</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->company->tax_id }}</dd>
                            <dt class="col-sm-4">Contacto</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->company->phone }} · {{ $registrationRequest->company->email }}</dd>
                            <dt class="col-sm-4">Ubicacion</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->company->address }} · {{ $registrationRequest->company->city }} · {{ $registrationRequest->company->country }}</dd>
                            <dt class="col-sm-4">Representante</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->company->legal_representative_first_name }} {{ $registrationRequest->company->legal_representative_last_name }} · {{ $registrationRequest->company->legal_representative_document_type }} {{ $registrationRequest->company->legal_representative_document_number }}</dd>
                            <dt class="col-sm-4">Descripcion</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->company->description }}</dd>
                            <dt class="col-sm-4">Archivos</dt>
                            <dd class="col-sm-8">
                                <div class="row g-3">
                                    @if ($registrationRequest->company->logo_url)
                                        <div class="col-md-6">
                                            <a class="d-block text-decoration-none" href="{{ $registrationRequest->company->logo_url }}" target="_blank" rel="noopener">
                                                <span class="d-block fw-semibold mb-2">Logo de la Empresa</span>
                                                <img class="img-fluid rounded border bg-white" src="{{ $registrationRequest->company->logo_url }}" alt="Logo de {{ $registrationRequest->company->name }}" style="max-height: 260px; object-fit: contain; width: 100%;">
                                            </a>
                                        </div>
                                    @endif
                                    @if ($registrationRequest->company->tax_document_url)
                                        <div class="col-md-6">
                                            <a class="d-block text-decoration-none" href="{{ $registrationRequest->company->tax_document_url }}" target="_blank" rel="noopener">
                                                <span class="d-block fw-semibold mb-2">Imagen/Fotografia del NIT</span>
                                                <img class="img-fluid rounded border bg-white" src="{{ $registrationRequest->company->tax_document_url }}" alt="Documento NIT de {{ $registrationRequest->company->name }}" style="max-height: 260px; object-fit: contain; width: 100%;">
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </dd>
                        @endif

                        @if ($registrationRequest->independentProfile)
                            <dt class="col-sm-4">Nombre comercial</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->company?->name ?: '-' }}</dd>
                            <dt class="col-sm-4">Nombre</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->independentProfile->first_name }} {{ $registrationRequest->independentProfile->last_name }}</dd>
                            <dt class="col-sm-4">Identificacion</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->independentProfile->document_number }}</dd>
                            <dt class="col-sm-4">Contacto</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->independentProfile->phone }} · {{ $registrationRequest->independentProfile->email }}</dd>
                            <dt class="col-sm-4">Trabajo</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->independentProfile->work_type }} · Guia certificado: {{ $registrationRequest->independentProfile->is_certified_guide ? 'Si' : 'No' }}</dd>
                            <dt class="col-sm-4">Descripcion</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->independentProfile->description }}</dd>
                            <dt class="col-sm-4">Archivos</dt>
                            <dd class="col-sm-8">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <a class="d-block text-decoration-none" href="{{ $registrationRequest->independentProfile->id_front_url }}" target="_blank" rel="noopener">
                                            <span class="d-block fw-semibold mb-2">Identificacion Anverso</span>
                                            <img class="img-fluid rounded border bg-white" src="{{ $registrationRequest->independentProfile->id_front_url }}" alt="Identificacion anverso" style="max-height: 240px; object-fit: contain; width: 100%;">
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a class="d-block text-decoration-none" href="{{ $registrationRequest->independentProfile->id_back_url }}" target="_blank" rel="noopener">
                                            <span class="d-block fw-semibold mb-2">Identificacion Reverso</span>
                                            <img class="img-fluid rounded border bg-white" src="{{ $registrationRequest->independentProfile->id_back_url }}" alt="Identificacion reverso" style="max-height: 240px; object-fit: contain; width: 100%;">
                                        </a>
                                    </div>
                                    <div class="col-md-4">
                                        <a class="d-block text-decoration-none" href="{{ $registrationRequest->independentProfile->profile_photo_url }}" target="_blank" rel="noopener">
                                            <span class="d-block fw-semibold mb-2">Fotografia Personal</span>
                                            <img class="img-fluid rounded border bg-white" src="{{ $registrationRequest->independentProfile->profile_photo_url }}" alt="Fotografia personal" style="max-height: 240px; object-fit: contain; width: 100%;">
                                        </a>
                                    </div>
                                </div>
                            </dd>
                        @endif

                        @if ($registrationRequest->rejection_reason)
                            <dt class="col-sm-4">Ultima observacion/motivo</dt>
                            <dd class="col-sm-8">{{ $registrationRequest->rejection_reason }}</dd>
                        @endif
                    </dl>
                </div>
            </x-ui.card>

            <x-ui.table-card title="Historial de revisiones">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Antes</th>
                            <th>Despues</th>
                            <th>Usuario</th>
                            <th>Observacion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($registrationRequest->reviews as $review)
                            <tr>
                                <td>{{ $review->reviewed_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $review->status_before ? str($review->status_before)->replace('_', ' ')->upper() : '-' }}</td>
                                <td>{{ str($review->status_after)->replace('_', ' ')->upper() }}</td>
                                <td>{{ $review->reviewer?->name ?? 'Sistema' }}</td>
                                <td>{{ $review->observation ?: '-' }}</td>
                            </tr>
                        @empty
                            <x-ui.empty-row colspan="5" message="Sin historial registrado." />
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>

        <div class="col-lg-4">
            <x-ui.card title="Acciones">
                <div class="card-body">
                    @if (in_array($registrationRequest->status, [\App\Models\RegistrationRequest::STATUS_PENDING, \App\Models\RegistrationRequest::STATUS_IN_REVIEW], true))
                        <form method="POST" action="{{ route('registration-requests.approve', $registrationRequest) }}" class="mb-3" data-confirm-delete="Aprobar esta solicitud?">
                            @csrf
                            <button class="btn btn-success w-100" type="submit">Aprobar</button>
                        </form>
                        <form method="POST" action="{{ route('registration-requests.observe', $registrationRequest) }}" class="mb-3">
                            @csrf
                            <label class="form-label" for="observation">Observaciones</label>
                            <textarea class="form-control @error('observation') is-invalid @enderror" id="observation" name="observation" rows="5" required>{{ old('observation') }}</textarea>
                            @error('observation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <button class="btn btn-warning w-100 mt-3" type="submit">Observar</button>
                        </form>
                        <form method="POST" action="{{ route('registration-requests.reject', $registrationRequest) }}">
                            @csrf
                            <label class="form-label" for="rejection_reason">Motivo de Rechazo Definitivo</label>
                            <textarea class="form-control @error('rejection_reason') is-invalid @enderror" id="rejection_reason" name="rejection_reason" rows="5" required>{{ old('rejection_reason') }}</textarea>
                            @error('rejection_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <button class="btn btn-danger w-100 mt-3" type="submit">Rechazar</button>
                        </form>
                    @else
                        <p class="text-muted mb-0">Esta solicitud ya fue procesada.</p>
                    @endif
                    <a class="btn btn-outline-secondary w-100 mt-3" href="{{ route('registration-requests.index') }}">Volver</a>
                </div>
            </x-ui.card>
        </div>
    </div>
@endsection
