@extends('layouts.public')

@section('title', 'Registro de Empresa | '.config('app.name', 'Tours'))

@section('content')
<section class="public-section">
    <div class="container-xl">
        <a class="public-back" href="{{ route('business-register.select') }}"><i class="ti ti-arrow-left"></i>Volver</a>
        <h1>Registro de Empresa</h1>
        <p class="text-muted">La cuenta quedara pendiente hasta que un Super Administrador revise la solicitud.</p>

        <form class="booking-form" method="POST" action="{{ route('business-register.company.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-section">
                <h2>Datos de Empresa</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nombre Comercial</label><input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Razon Social</label><input class="form-control @error('legal_name') is-invalid @enderror" name="legal_name" value="{{ old('legal_name') }}" required>@error('legal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label">NIT</label><input class="form-control @error('tax_id') is-invalid @enderror" name="tax_id" value="{{ old('tax_id') }}" required>@error('tax_id')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label">Telefono</label><input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" required>@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label">Correo Electronico</label><input class="form-control @error('email') is-invalid @enderror" name="email" type="email" value="{{ old('email') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Direccion</label><input class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address') }}" required>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-3"><label class="form-label">Ciudad</label><input class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city') }}" required>@error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-3"><label class="form-label">Pais</label><input class="form-control @error('country') is-invalid @enderror" name="country" value="{{ old('country', 'Bolivia') }}" required>@error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Sitio Web</label><input class="form-control @error('website') is-invalid @enderror" name="website" value="{{ old('website') }}">@error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><label class="form-label">Breve Descripcion</label><textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4" required>{{ old('description') }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
            </div>

            <div class="form-section">
                <h2>Representante Legal</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control @error('legal_representative_first_name') is-invalid @enderror" name="legal_representative_first_name" value="{{ old('legal_representative_first_name') }}" required>@error('legal_representative_first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Apellido</label><input class="form-control @error('legal_representative_last_name') is-invalid @enderror" name="legal_representative_last_name" value="{{ old('legal_representative_last_name') }}" required>@error('legal_representative_last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Numero de Identificacion</label><input class="form-control @error('legal_representative_document_number') is-invalid @enderror" name="legal_representative_document_number" value="{{ old('legal_representative_document_number') }}" required>@error('legal_representative_document_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Tipo de Documento</label><input class="form-control @error('legal_representative_document_type') is-invalid @enderror" name="legal_representative_document_type" value="{{ old('legal_representative_document_type', 'CI') }}" required>@error('legal_representative_document_type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Contraseña</label><input class="form-control @error('password') is-invalid @enderror" name="password" type="password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Confirmar Contraseña</label><input class="form-control" name="password_confirmation" type="password" required></div>
                </div>
            </div>

            <div class="form-section">
                <h2>Archivos Requeridos</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Logo de la Empresa</label><input class="form-control @error('logo') is-invalid @enderror" name="logo" type="file" accept="image/*" required>@error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Imagen/Fotografia del NIT</label><input class="form-control @error('tax_document') is-invalid @enderror" name="tax_document" type="file" accept="image/*" required>@error('tax_document')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
            </div>

            <button class="btn btn-primary btn-lg" type="submit">Enviar solicitud</button>
        </form>
    </div>
</section>
@endsection
