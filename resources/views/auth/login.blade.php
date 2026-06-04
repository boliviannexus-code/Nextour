<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Ingresar') }} | {{ config('app.name', 'Base Admin') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex flex-column">
<main class="page page-center">
    <div class="container container-tight py-4">
        <div class="text-center mb-4">
            <h1 class="h2">{{ config('app.name', 'Base Admin') }}</h1>
            <p class="text-muted">{{ __('Ingresa para administrar o gestionar tus reservas') }}</p>
        </div>
        <div class="card card-md">
            <div class="card-body">
                <h2 class="h2 text-center mb-4">{{ __('Iniciar sesión') }}</h2>
                    <form method="POST" action="{{ route('login.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">{{ __('Contraseña') }}</label>
                            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                            <label class="form-check-label" for="remember">{{ __('Recordarme') }}</label>
                        </div>
                        <button class="btn btn-primary w-100" type="submit">{{ __('Ingresar') }}</button>
                    </form>
                    <p class="text-center text-muted mt-3 mb-0">
                        {{ __('¿Eres turista?') }} <a href="{{ route('tourist.register') }}">{{ __('Crear cuenta') }}</a>
                    </p>
                    <div class="d-grid gap-2 mt-3">
                        {{-- <a class="btn btn-outline-success" href="{{ route('business-register.select') }}">{{ __('Registrar Empresa') }}</a> --}}
                        <a class="btn btn-outline-secondary" href="{{ route('public.home') }}">{{ __('Ir a la página principal') }}</a>
                        {{-- <a class="btn btn-outline-primary" href="{{ route('public.tours.index') }}">{{ __('Ver tours') }}</a> --}}
                    </div>
                     <p class="text-center text-muted mt-3 mb-0">
                        {{ __('¿Eres Guia?') }} <a href="{{ route('business-register.select') }}">{{ __('Registrar cuenta') }}</a>
                    </p>
            </div>
        </div>
    </div>
</main>
</body>
</html>
