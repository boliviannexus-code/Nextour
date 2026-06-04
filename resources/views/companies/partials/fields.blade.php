@php
    $canManageCompanyStatus = auth()->user()?->hasAnyRole(['super_admin', 'admin']);
    $registrationRequest = ($company ?? null)?->registrationRequest;
    $independentProfile = $registrationRequest?->independentProfile;
    $isIndependentRegistration = $registrationRequest?->type === \App\Models\RegistrationRequest::TYPE_INDEPENDENT && $independentProfile;
@endphp

@if ($isIndependentRegistration)
    <div class="form-section">
        <h2>Perfil Comercial</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="company-name">Nombre Comercial</label>
                <input class="form-control" id="company-name" name="name" value="{{ old('name', $company->name ?? '') }}" required>
                <div class="invalid-feedback" data-error-for="name"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="company-email">Correo Electronico</label>
                <input class="form-control" id="company-email" name="email" type="email" value="{{ old('email', $independentProfile->email ?? $company->email ?? '') }}">
                <div class="invalid-feedback" data-error-for="email"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="company-phone">Telefono</label>
                <input class="form-control" id="company-phone" name="phone" value="{{ old('phone', $independentProfile->phone ?? $company->phone ?? '') }}">
                <div class="invalid-feedback" data-error-for="phone"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="company-address">Direccion</label>
                <input class="form-control" id="company-address" name="address" value="{{ old('address', $independentProfile->address ?? $company->address ?? '') }}">
                <div class="invalid-feedback" data-error-for="address"></div>
            </div>
            <div class="col-12">
                <label class="form-label" for="company-description">Breve Descripcion</label>
                <textarea class="form-control" id="company-description" name="description" rows="4">{{ old('description', $independentProfile->description ?? $company->description ?? '') }}</textarea>
                <div class="invalid-feedback" data-error-for="description"></div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2>Datos Personales</h2>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="independent-first-name">Nombre</label>
                <input class="form-control" id="independent-first-name" name="first_name" value="{{ old('first_name', $independentProfile->first_name ?? '') }}" required>
                <div class="invalid-feedback" data-error-for="first_name"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="independent-last-name">Apellido</label>
                <input class="form-control" id="independent-last-name" name="last_name" value="{{ old('last_name', $independentProfile->last_name ?? '') }}" required>
                <div class="invalid-feedback" data-error-for="last_name"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="independent-document-number">Numero de Identificacion</label>
                <input class="form-control" id="independent-document-number" name="document_number" value="{{ old('document_number', $independentProfile->document_number ?? '') }}" required>
                <div class="invalid-feedback" data-error-for="document_number"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="independent-work-type">Tipo de Trabajo</label>
                <select class="form-select" id="independent-work-type" name="work_type" required>
                    <option value="">Seleccionar</option>
                    <option value="guide" @selected(old('work_type', $independentProfile->work_type ?? '') === 'guide')>Guia Turistico</option>
                    <option value="photographer" @selected(old('work_type', $independentProfile->work_type ?? '') === 'photographer')>Fotografo</option>
                    <option value="transport" @selected(old('work_type', $independentProfile->work_type ?? '') === 'transport')>Transportista</option>
                    <option value="operator" @selected(old('work_type', $independentProfile->work_type ?? '') === 'operator')>Operador Turistico</option>
                    <option value="artisan" @selected(old('work_type', $independentProfile->work_type ?? '') === 'artisan')>Artesano</option>
                    <option value="other" @selected(old('work_type', $independentProfile->work_type ?? '') === 'other')>Otro</option>
                </select>
                <div class="invalid-feedback" data-error-for="work_type"></div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="independent-certified-guide">¿Es Guia Turistico Certificado?</label>
                <select class="form-select" id="independent-certified-guide" name="is_certified_guide" required>
                    <option value="1" @selected((string) old('is_certified_guide', $independentProfile->is_certified_guide ? '1' : '0') === '1')>Si</option>
                    <option value="0" @selected((string) old('is_certified_guide', $independentProfile->is_certified_guide ? '1' : '0') === '0')>No</option>
                </select>
                <div class="invalid-feedback" data-error-for="is_certified_guide"></div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2>Documentos</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="independent-id-front">Identificacion Anverso</label>
                <input class="form-control" id="independent-id-front" name="id_front" type="file" accept="image/jpeg,image/png,image/webp">
                <div class="invalid-feedback" data-error-for="id_front"></div>
                @if ($independentProfile->id_front_url)
                    <a class="d-block mt-2" href="{{ $independentProfile->id_front_url }}" target="_blank" rel="noopener">
                        <img class="img-fluid rounded border bg-white" src="{{ $independentProfile->id_front_url }}" alt="Identificacion anverso" style="max-height: 140px; object-fit: contain; width: 100%;">
                    </a>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="independent-id-back">Identificacion Reverso</label>
                <input class="form-control" id="independent-id-back" name="id_back" type="file" accept="image/jpeg,image/png,image/webp">
                <div class="invalid-feedback" data-error-for="id_back"></div>
                @if ($independentProfile->id_back_url)
                    <a class="d-block mt-2" href="{{ $independentProfile->id_back_url }}" target="_blank" rel="noopener">
                        <img class="img-fluid rounded border bg-white" src="{{ $independentProfile->id_back_url }}" alt="Identificacion reverso" style="max-height: 140px; object-fit: contain; width: 100%;">
                    </a>
                @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="independent-profile-photo">Fotografia Personal</label>
                <input class="form-control" id="independent-profile-photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp">
                <div class="invalid-feedback" data-error-for="profile_photo"></div>
                @if ($independentProfile->profile_photo_url)
                    <a class="d-block mt-2" href="{{ $independentProfile->profile_photo_url }}" target="_blank" rel="noopener">
                        <img class="img-fluid rounded border bg-white" src="{{ $independentProfile->profile_photo_url }}" alt="Fotografia personal" style="max-height: 140px; object-fit: contain; width: 100%;">
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="form-section">
        <h2>Datos administrativos</h2>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="company-report-footer">Pie de reporte</label>
                <textarea class="form-control" id="company-report-footer" name="report_footer" rows="3">{{ old('report_footer', $company->report_footer ?? '') }}</textarea>
                <div class="invalid-feedback" data-error-for="report_footer"></div>
            </div>
        </div>

        @if ($canManageCompanyStatus)
            <input type="hidden" name="is_active" value="0">
            <div class="form-check form-switch mt-4">
                <input class="form-check-input" id="company-is-active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $company->is_active ?? true))>
                <label class="form-check-label" for="company-is-active">Activo</label>
                <div class="invalid-feedback d-block" data-error-for="is_active"></div>
            </div>
        @endif
    </div>
@else

<div class="form-section">
    <h2>Datos de Empresa</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="company-name">Nombre Comercial</label>
            <input class="form-control" id="company-name" name="name" value="{{ old('name', $company->name ?? '') }}" required>
            <div class="invalid-feedback" data-error-for="name"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="company-legal-name">Razon Social</label>
            <input class="form-control" id="company-legal-name" name="legal_name" value="{{ old('legal_name', $company->legal_name ?? '') }}">
            <div class="invalid-feedback" data-error-for="legal_name"></div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="company-tax-id">NIT</label>
            <input class="form-control" id="company-tax-id" name="tax_id" value="{{ old('tax_id', $company->tax_id ?? '') }}">
            <div class="invalid-feedback" data-error-for="tax_id"></div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="company-phone">Telefono</label>
            <input class="form-control" id="company-phone" name="phone" value="{{ old('phone', $company->phone ?? '') }}">
            <div class="invalid-feedback" data-error-for="phone"></div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="company-email">Correo Electronico</label>
            <input class="form-control" id="company-email" name="email" type="email" value="{{ old('email', $company->email ?? '') }}">
            <div class="invalid-feedback" data-error-for="email"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="company-address">Direccion</label>
            <input class="form-control" id="company-address" name="address" value="{{ old('address', $company->address ?? '') }}">
            <div class="invalid-feedback" data-error-for="address"></div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="company-city">Ciudad</label>
            <input class="form-control" id="company-city" name="city" value="{{ old('city', $company->city ?? '') }}">
            <div class="invalid-feedback" data-error-for="city"></div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="company-country">Pais</label>
            <input class="form-control" id="company-country" name="country" value="{{ old('country', $company->country ?? 'Bolivia') }}">
            <div class="invalid-feedback" data-error-for="country"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="company-website">Sitio Web</label>
            <input class="form-control" id="company-website" name="website" value="{{ old('website', $company->website ?? '') }}">
            <div class="invalid-feedback" data-error-for="website"></div>
        </div>
        <div class="col-12">
            <label class="form-label" for="company-description">Breve Descripcion</label>
            <textarea class="form-control" id="company-description" name="description" rows="4">{{ old('description', $company->description ?? '') }}</textarea>
            <div class="invalid-feedback" data-error-for="description"></div>
        </div>
    </div>
</div>

<div class="form-section">
    <h2>Representante Legal</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="company-rep-first-name">Nombre</label>
            <input class="form-control" id="company-rep-first-name" name="legal_representative_first_name" value="{{ old('legal_representative_first_name', $company->legal_representative_first_name ?? '') }}">
            <div class="invalid-feedback" data-error-for="legal_representative_first_name"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="company-rep-last-name">Apellido</label>
            <input class="form-control" id="company-rep-last-name" name="legal_representative_last_name" value="{{ old('legal_representative_last_name', $company->legal_representative_last_name ?? '') }}">
            <div class="invalid-feedback" data-error-for="legal_representative_last_name"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="company-rep-doc-number">Numero de Identificacion</label>
            <input class="form-control" id="company-rep-doc-number" name="legal_representative_document_number" value="{{ old('legal_representative_document_number', $company->legal_representative_document_number ?? '') }}">
            <div class="invalid-feedback" data-error-for="legal_representative_document_number"></div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="company-rep-doc-type">Tipo de Documento</label>
            <input class="form-control" id="company-rep-doc-type" name="legal_representative_document_type" value="{{ old('legal_representative_document_type', $company->legal_representative_document_type ?? 'CI') }}">
            <div class="invalid-feedback" data-error-for="legal_representative_document_type"></div>
        </div>
    </div>
</div>

<div class="form-section">
    <h2>Archivos</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="company-logo">Logo de la Empresa</label>
            <input class="form-control" id="company-logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
            <div class="form-hint">JPG, PNG o WebP. Maximo 8 MB.</div>
            <div class="invalid-feedback" data-error-for="logo"></div>
            @if (($company ?? null)?->logo_url)
                <div class="d-flex align-items-center gap-3 mt-2">
                    <img class="avatar avatar-lg" src="{{ $company->logo_url }}" alt="{{ $company->name }}">
                    <label class="form-check m-0">
                        <input class="form-check-input" name="remove_logo" type="checkbox" value="1" @checked(old('remove_logo'))>
                        <span class="form-check-label">Quitar logo actual</span>
                    </label>
                </div>
                <div class="invalid-feedback d-block" data-error-for="remove_logo"></div>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label" for="company-tax-document">Imagen/Fotografia del NIT</label>
            <input class="form-control" id="company-tax-document" name="tax_document" type="file" accept="image/jpeg,image/png,image/webp">
            <div class="form-hint">JPG, PNG o WebP. Maximo 8 MB.</div>
            <div class="invalid-feedback" data-error-for="tax_document"></div>
            @if (($company ?? null)?->tax_document_url)
                <div class="d-flex align-items-center gap-3 mt-2">
                    <img class="avatar avatar-lg" src="{{ $company->tax_document_url }}" alt="NIT {{ $company->name }}">
                    <label class="form-check m-0">
                        <input class="form-check-input" name="remove_tax_document" type="checkbox" value="1" @checked(old('remove_tax_document'))>
                        <span class="form-check-label">Quitar documento actual</span>
                    </label>
                </div>
                <div class="invalid-feedback d-block" data-error-for="remove_tax_document"></div>
            @endif
        </div>
    </div>
</div>

<div class="form-section">
    <h2>Datos administrativos</h2>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="company-report-footer">Pie de reporte</label>
            <textarea class="form-control" id="company-report-footer" name="report_footer" rows="3">{{ old('report_footer', $company->report_footer ?? '') }}</textarea>
            <div class="invalid-feedback" data-error-for="report_footer"></div>
        </div>
    </div>

    @if ($canManageCompanyStatus)
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch mt-4">
            <input class="form-check-input" id="company-is-active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $company->is_active ?? true))>
            <label class="form-check-label" for="company-is-active">Activo</label>
            <div class="invalid-feedback d-block" data-error-for="is_active"></div>
        </div>
    @endif
</div>
@endif
