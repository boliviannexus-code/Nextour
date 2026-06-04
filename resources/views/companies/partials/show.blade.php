<div class="d-flex align-items-center gap-3 mb-3">
    @if ($company->logo_url)
        <img class="avatar avatar-xl" src="{{ $company->logo_url }}" alt="{{ $company->name }}">
    @else
        <span class="avatar avatar-xl bg-primary-lt text-primary"><i class="ti ti-building fs-2"></i></span>
    @endif
    <div>
        <div class="h2 mb-1">{{ $company->name }}</div>
        <div class="text-body-secondary">{{ $company->legal_name ?: 'Sin razon social' }}</div>
    </div>
</div>

<dl class="row mb-0">
    <dt class="col-sm-4">NIT</dt>
    <dd class="col-sm-8">{{ $company->tax_id ?: '-' }}</dd>
    <dt class="col-sm-4">Telefono</dt>
    <dd class="col-sm-8">{{ $company->phone ?: '-' }}</dd>
    <dt class="col-sm-4">Correo Electronico</dt>
    <dd class="col-sm-8">{{ $company->email ?: '-' }}</dd>
    <dt class="col-sm-4">Sitio Web</dt>
    <dd class="col-sm-8">
        @if ($company->website)
            <a href="{{ $company->website }}" target="_blank" rel="noopener">{{ $company->website }}</a>
        @else
            -
        @endif
    </dd>
    <dt class="col-sm-4">Direccion</dt>
    <dd class="col-sm-8">{{ $company->address ?: '-' }}</dd>
    <dt class="col-sm-4">Ciudad/Pais</dt>
    <dd class="col-sm-8">{{ trim(($company->city ?: '').' / '.($company->country ?: ''), ' /') ?: '-' }}</dd>
    <dt class="col-sm-4">Breve Descripcion</dt>
    <dd class="col-sm-8">{{ $company->description ?: '-' }}</dd>
    <dt class="col-sm-4">Representante Legal</dt>
    <dd class="col-sm-8">
        {{ trim(($company->legal_representative_first_name ?: '').' '.($company->legal_representative_last_name ?: '')) ?: '-' }}
        @if ($company->legal_representative_document_number)
            <div class="text-body-secondary small">
                {{ $company->legal_representative_document_type ?: 'Documento' }} {{ $company->legal_representative_document_number }}
            </div>
        @endif
    </dd>
    <dt class="col-sm-4">Usuarios asignados</dt>
    <dd class="col-sm-8">{{ $company->users_count }}</dd>
    <dt class="col-sm-4">Pie de reporte</dt>
    <dd class="col-sm-8">{{ $company->report_footer ?: '-' }}</dd>
    <dt class="col-sm-4">Estado</dt>
    <dd class="col-sm-8"><span class="badge text-bg-{{ $company->is_active ? 'success' : 'secondary' }}">{{ $company->is_active ? 'Activo' : 'Inactivo' }}</span></dd>
    <dt class="col-sm-4">Estado de aprobacion</dt>
    <dd class="col-sm-8">{{ str($company->approval_status ?? \App\Models\RegistrationRequest::STATUS_PENDING)->replace('_', ' ')->upper() }}</dd>
    @if ($company->registrationRequest)
        <dt class="col-sm-4">Solicitud de registro</dt>
        <dd class="col-sm-8">
            <span class="badge text-bg-{{ $company->registrationRequest->status_badge }}">{{ $company->registrationRequest->status_label }}</span>
            @role('super_admin')
                <a class="btn btn-outline-secondary btn-sm ms-2" href="{{ route('registration-requests.show', $company->registrationRequest) }}">Ver solicitud</a>
            @endrole
        </dd>
        @if (
            in_array($company->registrationRequest->status, [\App\Models\RegistrationRequest::STATUS_PENDING, \App\Models\RegistrationRequest::STATUS_OBSERVED], true)
            && (int) $company->registrationRequest->user_id === (int) auth()->id()
        )
            <dt class="col-sm-4">Observaciones</dt>
            <dd class="col-sm-8">{{ $company->registrationRequest->rejection_reason ?: '-' }}</dd>
            <dt class="col-sm-4">Reenviar solicitud</dt>
            <dd class="col-sm-8">
                <form method="POST" action="{{ route('registration-requests.resubmit', $company->registrationRequest) }}" data-confirm-delete="Reenviar solicitud para nueva revision?">
                    @csrf
                    <button class="btn btn-primary" type="submit">Enviar nuevamente a revision</button>
                </form>
                <div class="form-hint mt-2">Antes de reenviar, actualiza los datos observados desde la opcion Editar.</div>
            </dd>
        @elseif (
            $company->registrationRequest->status === \App\Models\RegistrationRequest::STATUS_REJECTED
            && (int) $company->registrationRequest->user_id === (int) auth()->id()
        )
            <dt class="col-sm-4">Motivo de rechazo</dt>
            <dd class="col-sm-8">{{ $company->registrationRequest->rejection_reason ?: '-' }}</dd>
        @endif
    @endif
    <dt class="col-sm-4">Archivos</dt>
    <dd class="col-sm-8">
        <div class="row g-3">
            @if ($company->logo_url)
                <div class="col-md-6">
                    <a class="d-block text-decoration-none" href="{{ $company->logo_url }}" target="_blank" rel="noopener">
                        <span class="d-block fw-semibold mb-2">Logo de la Empresa</span>
                        <img class="img-fluid rounded border bg-white" src="{{ $company->logo_url }}" alt="Logo de {{ $company->name }}" style="max-height: 240px; object-fit: contain; width: 100%;">
                    </a>
                </div>
            @endif
            @if ($company->tax_document_url)
                <div class="col-md-6">
                    <a class="d-block text-decoration-none" href="{{ $company->tax_document_url }}" target="_blank" rel="noopener">
                        <span class="d-block fw-semibold mb-2">Imagen/Fotografia del NIT</span>
                        <img class="img-fluid rounded border bg-white" src="{{ $company->tax_document_url }}" alt="Documento NIT de {{ $company->name }}" style="max-height: 240px; object-fit: contain; width: 100%;">
                    </a>
                </div>
            @endif
            @unless ($company->logo_url || $company->tax_document_url)
                <div class="col-12 text-body-secondary">Sin archivos cargados.</div>
            @endunless
        </div>
    </dd>
</dl>
