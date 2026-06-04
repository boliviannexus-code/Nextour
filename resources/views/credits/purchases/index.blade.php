@extends('layouts.admin')

@section('title', 'Comprar Creditos | '.config('app.name', 'Base Admin'))
@section('page-title', 'Comprar Creditos')
@section('page-subtitle', 'Compra manual con comprobante. Todos los precios estan en USD')

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="row g-3">
        <div class="col-12">
            <x-ui.card title="Saldo actual">
                <div class="card-body"><div class="h2 mb-0">{{ $balance->credits_balance }} creditos</div></div>
            </x-ui.card>
        </div>

        @forelse ($packages as $package)
            <div class="col-md-6 col-xl-4">
                <x-ui.card :title="$package->name">
                    <div class="card-body">
                        <div class="h2 mb-1">{{ $package->credits_amount }} creditos</div>
                        <div class="h3 text-primary">USD {{ number_format((float) $package->price, 2) }}</div>
                        <p class="text-body-secondary">{{ $package->description }}</p>
                        @if ($package->payment_qr_url)
                            <a href="{{ $package->payment_qr_url }}" target="_blank" rel="noopener"><img class="img-fluid rounded border mb-3" src="{{ $package->payment_qr_url }}" alt="QR de pago {{ $package->name }}"></a>
                        @endif
                        <form method="POST" action="{{ route('credit-purchases.store') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="credit_package_id" value="{{ $package->id }}">
                            <label class="form-label">Comprobante</label>
                            <input class="form-control mb-2" name="payment_proof" type="file" accept="image/jpeg,image/png,application/pdf" required>
                            <button class="btn btn-primary w-100" type="submit">Solicitar compra</button>
                        </form>
                    </div>
                </x-ui.card>
            </div>
        @empty
            <div class="col-12"><x-ui.card><div class="card-body text-body-secondary">No hay paquetes activos disponibles.</div></x-ui.card></div>
        @endforelse
    </div>

    <x-ui.table-card title="Historial de solicitudes" class="mt-3">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Paquete</th><th class="text-end">Creditos</th><th class="text-end">Monto</th><th>Estado</th><th>Observacion</th></tr></thead>
            <tbody>
                @forelse ($purchaseRequests as $request)
                    <tr>
                        <td>{{ $request->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $request->package?->name ?? 'Paquete eliminado' }}</td>
                        <td class="text-end">{{ $request->requested_credits }}</td>
                        <td class="text-end">{{ $request->currency }} {{ number_format((float) $request->amount, 2) }}</td>
                        <td><span class="badge text-bg-{{ match($request->status) { 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary', default => 'warning' } }}">{{ $request->status_label }}</span></td>
                        <td>{{ $request->admin_observation ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td class="text-center text-body-secondary py-3" colspan="6">Aun no hay solicitudes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.table-card>
@endsection
