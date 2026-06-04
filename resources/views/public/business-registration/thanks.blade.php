@extends('layouts.public')

@section('title', 'Solicitud recibida | '.config('app.name', 'Tours'))

@section('content')
<section class="public-section">
    <div class="container-xl">
        <div class="public-empty">
            <h1>Solicitud recibida</h1>
            <p>Su solicitud ha sido recibida y esta pendiente de revision. Le enviaremos un correo cuando sea aprobada o rechazada.</p>
            <a class="btn btn-primary" href="{{ route('login') }}">Volver al login</a>
        </div>
    </div>
</section>
@endsection
