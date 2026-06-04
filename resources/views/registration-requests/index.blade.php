@extends('layouts.admin')

@section('title', 'Solicitudes de Registro | '.config('app.name', 'Base Admin'))
@section('page-title', 'Gestion de Empresas')
@section('page-subtitle', 'Revision, observacion, rechazo y aprobacion de empresas')

@section('content')
    <x-ui.table-card title="Gestion de Solicitudes">
        <x-slot:actions>
            <div class="btn-list">
                <a class="btn btn-sm {{ ! $status || $status === 'all' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('registration-requests.index', ['status' => 'all']) }}">Todas</a>
                @foreach (\App\Models\RegistrationRequest::statuses() as $requestStatus)
                    <a class="btn btn-sm {{ $status === $requestStatus ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('registration-requests.index', ['status' => $requestStatus]) }}">{{ str($requestStatus)->replace('_', ' ')->headline() }}</a>
                @endforeach
            </div>
        </x-slot:actions>

        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Responsable</th>
                    <th>Email</th>
                    <th>Fecha de Registro</th>
                    <th>Estado</th>
                    <th>Ultima Revision</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $registrationRequest)
                    <tr>
                        <td>{{ $registrationRequest->company?->name ?? $registrationRequest->display_name }}</td>
                        <td>{{ $registrationRequest->user?->name }}</td>
                        <td>{{ $registrationRequest->display_email }}</td>
                        <td>{{ $registrationRequest->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <span class="badge text-bg-{{ $registrationRequest->status_badge }}">{{ $registrationRequest->status_label }}</span>
                        </td>
                        <td>{{ $registrationRequest->last_reviewed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                        <td class="text-end">
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('registration-requests.show', $registrationRequest) }}">Ver detalle</a>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="7" message="No hay solicitudes con ese filtro." />
                @endforelse
            </tbody>
        </table>

        <x-slot:footer>{{ $requests->links() }}</x-slot:footer>
    </x-ui.table-card>
@endsection
