@extends('layouts.admin')

@section('title', 'Solicitudes de Creditos | '.config('app.name', 'Base Admin'))
@section('page-title', 'Solicitudes de Creditos')
@section('page-subtitle', 'Revision manual de comprobantes en USD')

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <x-ui.table-card title="Solicitudes">
        <x-slot:actions>
            <form class="d-flex flex-wrap gap-2" method="GET">
                <select class="form-select form-select-sm" name="status">
                    <option value="">Todos</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="form-select form-select-sm" name="company_id">
                    <option value="">Empresa</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}" @selected((string)($filters['company_id'] ?? '') === (string)$company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                <input class="form-control form-control-sm" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}">
                <input class="form-control form-control-sm" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}">
                <button class="btn btn-outline-primary btn-sm" type="submit">Filtrar</button>
            </form>
        </x-slot:actions>
        <table class="table table-sm table-hover align-middle mb-0">
            <thead><tr><th>Fecha</th><th>Empresa</th><th>Paquete</th><th class="text-end">Creditos</th><th class="text-end">Monto</th><th>Comprobante</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse ($requests as $item)
                    <tr>
                        <td>{{ $item->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $item->company?->name }}</td>
                        <td>{{ $item->package?->name ?? 'Paquete eliminado' }}</td>
                        <td class="text-end">{{ $item->requested_credits }}</td>
                        <td class="text-end">{{ $item->currency }} {{ number_format((float) $item->amount, 2) }}</td>
                        <td><a href="{{ $item->payment_proof_url }}" target="_blank" rel="noopener">Ver</a></td>
                        <td><span class="badge text-bg-{{ match($item->status) { 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary', default => 'warning' } }}">{{ $item->status_label }}</span></td>
                        <td class="text-end">
                            @if ($item->status === \App\Models\CreditPurchaseRequest::STATUS_PENDING)
                                <form class="d-inline" method="POST" action="{{ route('credit-purchases.requests.approve', $item) }}">
                                    @csrf
                                    <button class="btn btn-outline-success btn-sm" type="submit">Aprobar</button>
                                </form>
                                <form class="d-inline-flex gap-1" method="POST" action="{{ route('credit-purchases.requests.reject', $item) }}">
                                    @csrf
                                    <input class="form-control form-control-sm" name="admin_observation" placeholder="Observacion" required>
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Rechazar</button>
                                </form>
                            @else
                                <span class="text-body-secondary small">{{ $item->admin_observation ?: '-' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-center text-body-secondary py-3" colspan="8">No hay solicitudes.</td></tr>
                @endforelse
            </tbody>
        </table>
        <x-slot:footer>{{ $requests->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
