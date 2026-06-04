@extends('layouts.public')

@section('title', __('Perfil turista'))

@section('content')
<section class="account-shell">
    <div class="container-xl">
        @include('public.account.partials.nav')
        <h1>{{ __('Perfil del turista') }}</h1>
        <div class="profile-panel">
            <div><span>{{ __('Nombre') }}</span><strong>{{ $user->name }}</strong></div>
            <div><span>Email</span><strong>{{ $user->email }}</strong></div>
            <div><span>{{ __('Cuenta') }}</span><strong>{{ __('Turista') }}</strong></div>
        </div>
    </div>
</section>
@endsection
