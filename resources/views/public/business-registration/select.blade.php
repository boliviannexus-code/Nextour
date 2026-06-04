@extends('layouts.public')

@section('title', 'Registrar Empresa | '.config('app.name', 'Tours'))

@section('content')
<section class="public-section">
    <div class="container-xl">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Registro comercial</span>
                <h1>Elige el tipo de solicitud</h1>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <article class="card h-100">
                    <div class="card-body">
                        <span class="avatar bg-primary-lt text-primary mb-3"><i class="ti ti-building-store fs-3"></i></span>
                        <h2 class="h3">Empresa</h2>
                        <p class="text-muted">Registra una agencia, operador turistico, hospedaje o empresa formal con representante legal.</p>
                        <a class="btn btn-primary" href="{{ route('business-register.company') }}">Registrar Empresa</a>
                    </div>
                </article>
            </div>
            <div class="col-md-6">
                <article class="card h-100">
                    <div class="card-body">
                        <span class="avatar bg-success-lt text-success mb-3"><i class="ti ti-user-star fs-3"></i></span>
                        <h2 class="h3">Independiente</h2>
                        <p class="text-muted">Registra un guia, fotografo, transportista, artesano u operador independiente.</p>
                        <a class="btn btn-success" href="{{ route('business-register.independent') }}">Registrar Independiente</a>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>
@endsection
