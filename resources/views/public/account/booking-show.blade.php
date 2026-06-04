@extends('layouts.public')

@section('title', __('Reserva').' '.$booking->booking_code)

@section('content')
<section class="account-shell">
    <div class="container-xl detail-layout">
        <article class="detail-content">
            <a class="public-back" href="{{ route('tourist.reservations.index') }}"><i class="ti ti-arrow-left"></i>{{ __('Mis reservas') }}</a>
            <h1>{{ __('Reserva') }} {{ $booking->booking_code }}</h1>
            <x-public.booking-status :status="$booking->status" />
            <div class="quick-facts mt-4">
                <div><i class="ti ti-calendar"></i><strong>{{ __('Fecha') }}</strong><span>{{ $booking->travel_date->translatedFormat('d M Y') }}</span></div>
                <div><i class="ti ti-users"></i><strong>{{ __('Personas') }}</strong><span>{{ $booking->people }}</span></div>
                <div><i class="ti ti-cash"></i><strong>Total</strong><span>${{ number_format((float) $booking->total_usd, 2) }}</span></div>
            </div>
            <h2>{{ $booking->tour->display_title }}</h2>
            <p>{{ $booking->tour->short_description ?: $booking->tour->description }}</p>
            <h2>{{ __('Datos del turista') }}</h2>
            <p>{{ $booking->first_name }} {{ $booking->last_name }} · {{ $booking->email }} · {{ $booking->phone }} · {{ $booking->country }}</p>
            @if ($booking->special_requirements)
                <h2>{{ __('Requerimientos especiales') }}</h2>
                <p>{{ $booking->special_requirements }}</p>
            @endif
        </article>
        <aside class="booking-box">
            <h2>Voucher</h2>
            <p class="text-muted">{{ __('Presenta este comprobante el día del tour.') }}</p>
            <a class="btn btn-primary w-100" href="{{ route('tourist.reservations.voucher', $booking) }}">{{ __('Descargar voucher') }}</a>
        </aside>
    </div>
</section>
@endsection
