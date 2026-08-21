@extends('layouts.admin')

@section('title', 'Respaldos de base de datos | '.config('app.name'))
@section('page-title', 'Respaldos de base de datos')
@section('page-subtitle', 'Generacion, descarga y restauracion segura de PostgreSQL')

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <form method="POST" action="{{ route('database-backups.store') }}">
            @csrf
            <button class="btn btn-primary" type="submit">
                <i class="ti ti-database-export me-1"></i>Generar respaldo SQL
            </button>
        </form>
    </div>

    @error('backup')
        <div class="alert alert-danger" role="alert">{{ $message }}</div>
    @enderror

    <div class="row row-cards">
        <div class="col-xl-8">
            <x-ui.table-card title="Respaldos generados">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Archivo</th>
                            <th class="text-end">Tamano</th>
                            <th>Fecha</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backups as $backup)
                            <tr>
                                <td class="fw-semibold">{{ $backup['name'] }}</td>
                                <td class="text-end">{{ number_format($backup['size'] / 1024, 1) }} KB</td>
                                <td>{{ $backup['created_at']?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('database-backups.download', $backup['name']) }}">
                                        <i class="ti ti-download me-1"></i>Descargar
                                    </a>
                                    <form class="d-inline" method="POST" action="{{ route('database-backups.restore', $backup['name']) }}" data-confirm-delete="Restaurar este respaldo reemplazara todos los datos actuales. Continuar?">
                                        @csrf
                                        <input type="hidden" name="confirm_restore" value="1">
                                        <button class="btn btn-outline-danger btn-sm" type="submit"><i class="ti ti-database-import me-1"></i>Restaurar</button>
                                    </form>
                                    <form class="d-inline" method="POST" action="{{ route('database-backups.destroy', $backup['name']) }}" data-confirm-delete="Eliminar este respaldo?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="ti ti-trash me-1"></i>Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-ui.empty-row colspan="4" message="Aun no hay respaldos generados." />
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>

        <div class="col-xl-4">
            <x-ui.card title="Restaurar desde archivo">
                <div class="card-body">
                    <p class="text-body-secondary small">Solo se aceptan archivos SQL firmados por esta instalacion.</p>
                    <form method="POST" action="{{ route('database-backups.restore-upload') }}" enctype="multipart/form-data" data-confirm-delete="Esta accion reemplazara todos los datos actuales. Continuar?">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="backup-upload">Archivo SQL</label>
                            <input id="backup-upload" class="form-control @error('backup') is-invalid @enderror" type="file" name="backup" accept=".sql,application/sql,text/plain" required>
                        </div>
                        <label class="form-check mb-3">
                            <input class="form-check-input @error('confirm_restore') is-invalid @enderror" type="checkbox" name="confirm_restore" value="1" required>
                            <span class="form-check-label">Confirmo que deseo reemplazar los datos actuales.</span>
                        </label>
                        @error('confirm_restore')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
                        <button class="btn btn-danger w-100" type="submit"><i class="ti ti-database-import me-1"></i>Restaurar archivo SQL</button>
                    </form>
                </div>
            </x-ui.card>
        </div>
    </div>
@endsection
