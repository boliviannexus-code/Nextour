@extends('layouts.public')

@section('title', 'Registro Independiente | '.config('app.name', 'Tours'))

@section('content')
<section class="public-section">
    <div class="container-xl">
        <a class="public-back" href="{{ route('business-register.select') }}"><i class="ti ti-arrow-left"></i>Volver</a>
        <h1>Registro Independiente</h1>
        <p class="text-muted">Tu cuenta quedara pendiente hasta la aprobacion del Super Administrador.</p>

        <form class="booking-form" method="POST" action="{{ route('business-register.independent.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-section">
                <h2>Perfil Comercial</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nombre Comercial</label><input class="form-control @error('commercial_name') is-invalid @enderror" name="commercial_name" value="{{ old('commercial_name') }}" required>@error('commercial_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
            </div>

            <div class="form-section">
                <h2>Datos Personales</h2>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Nombre</label><input class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required>@error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Apellido</label><input class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required>@error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Numero de Identificacion</label><input class="form-control @error('document_number') is-invalid @enderror" name="document_number" value="{{ old('document_number') }}" required>@error('document_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Telefono</label><input class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone') }}" required>@error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Correo Electronico</label><input class="form-control @error('email') is-invalid @enderror" name="email" type="email" value="{{ old('email') }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Direccion</label><input class="form-control @error('address') is-invalid @enderror" name="address" value="{{ old('address') }}" required>@error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Trabajo</label>
                        <select class="form-select @error('work_type') is-invalid @enderror" name="work_type" required>
                            <option value="">Seleccionar</option>
                            <option value="guide" @selected(old('work_type') === 'guide')>Guia Turistico</option>
                            <option value="photographer" @selected(old('work_type') === 'photographer')>Fotografo</option>
                            <option value="transport" @selected(old('work_type') === 'transport')>Transportista</option>
                            <option value="operator" @selected(old('work_type') === 'operator')>Operador Turistico</option>
                            <option value="artisan" @selected(old('work_type') === 'artisan')>Artesano</option>
                            <option value="other" @selected(old('work_type') === 'other')>Otro</option>
                        </select>
                        @error('work_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">¿Es Guia Turistico Certificado?</label>
                        <select class="form-select @error('is_certified_guide') is-invalid @enderror" name="is_certified_guide" required>
                            <option value="1" @selected(old('is_certified_guide') === '1')>Si</option>
                            <option value="0" @selected(old('is_certified_guide', '0') === '0')>No</option>
                        </select>
                        @error('is_certified_guide')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12"><label class="form-label">Breve Descripcion de su Trabajo</label><textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="4" required>{{ old('description') }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Contraseña</label><input class="form-control @error('password') is-invalid @enderror" name="password" type="password" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-6"><label class="form-label">Confirmar Contraseña</label><input class="form-control" name="password_confirmation" type="password" required></div>
                </div>
            </div>

            <div class="form-section">
                <h2>Archivos Requeridos</h2>
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Identificacion Anverso</label><input class="form-control @error('id_front') is-invalid @enderror" name="id_front" type="file" accept="image/*" required>@error('id_front')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label">Identificacion Reverso</label><input class="form-control @error('id_back') is-invalid @enderror" name="id_back" type="file" accept="image/*" required>@error('id_back')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-md-4"><label class="form-label">Fotografia Personal</label><input class="form-control @error('profile_photo') is-invalid @enderror" name="profile_photo" type="file" accept="image/*" required>@error('profile_photo')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
            </div>

            <button class="btn btn-success btn-lg" type="submit">Enviar solicitud</button>
        </form>
    </div>
</section>
@endsection
