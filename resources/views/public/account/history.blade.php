@extends('layouts.public')

@section('title', __('Historial de tours'))

@section('content')
<section class="account-shell">
    <div class="container-xl">
        @include('public.account.partials.nav')
        <h1>{{ __('Historial de tours') }}</h1>
        <div class="booking-list">
            @forelse ($bookings as $booking)
                <article class="booking-row">
                    <div><strong>{{ $booking->tour->display_title }}</strong><span>{{ $booking->travel_date->translatedFormat('d M Y') }}</span></div>
                    <x-public.booking-status :status="$booking->status" />
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('tourist.reservations.show', $booking) }}">{{ __('Ver') }}</a>
                </article>
            @empty
                <div class="public-empty">{{ __('Tu historial aparecerá aquí después de viajar.') }}</div>
            @endforelse
        </div>
    </div>
</section>
@endsection
