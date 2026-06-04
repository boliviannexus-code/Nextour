@extends('layouts.public')

@section('title', __('Crear cuenta turista'))

@section('content')
<section class="auth-public">
    <div class="auth-panel">
        <span class="eyebrow">{{ __('Cuenta turista') }}</span>
        <h1>{{ __('Crea tu cuenta para reservar tours') }}</h1>
        <form method="POST" action="{{ route('tourist.register.store') }}">
            @csrf
            <label class="form-label" for="name">{{ __('Nombre completo') }}</label>
            <input class="form-control mb-3 @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

            <label class="form-label" for="email">Email</label>
            <input class="form-control mb-3 @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

            <label class="form-label" for="password">{{ __('Contraseña') }}</label>
            <input class="form-control mb-3 @error('password') is-invalid @enderror" id="password" name="password" type="password" required>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

            <label class="form-label" for="password_confirmation">{{ __('Confirmar contraseña') }}</label>
            <input class="form-control mb-4" id="password_confirmation" name="password_confirmation" type="password" required>

            <button class="btn btn-primary w-100" type="submit">{{ __('Crear cuenta') }}</button>
        </form>
        <p class="mt-3 mb-0 text-muted">{{ __('¿Ya tienes cuenta?') }} <a href="{{ route('login') }}">{{ __('Ingresar') }}</a></p>
        <div class="d-grid gap-2 mt-3">
            <a class="btn btn-outline-secondary" href="{{ route('public.home') }}">{{ __('Ir a la página principal') }}</a>
        </div>
        <p class="mt-3 mb-0 text-muted">{{ __('¿Eres Guia?') }} <a href="{{ route('business-register.select') }}">{{ __('Registrar cuenta') }}</a></p>
    </div>
</section>
@endsection
