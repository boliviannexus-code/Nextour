@extends('layouts.admin')

@section('title', 'Paquetes de Creditos | '.config('app.name', 'Base Admin'))
@section('page-title', 'Paquetes de Creditos')
@section('page-subtitle', 'Precios configurados exclusivamente en USD')

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @error('package')<div class="alert alert-danger">{{ $message }}</div>@enderror

    <div class="row g-3">
        <div class="col-lg-5">
            <x-ui.form-panel :action="$package ? route('credit-packages.update', $package) : route('credit-packages.store')" :method="$package ? 'PUT' : 'POST'" enctype="multipart/form-data" :title="$package ? 'Editar paquete' : 'Nuevo paquete'">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Nombre</label>
                        <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $package->name ?? '') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Creditos</label>
                        <input class="form-control @error('credits_amount') is-invalid @enderror" name="credits_amount" type="number" min="1" value="{{ old('credits_amount', $package->credits_amount ?? '') }}" required>
                        @error('credits_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Precio USD</label>
                        <input class="form-control @error('price') is-invalid @enderror" name="price" type="number" min="0.01" step="0.01" value="{{ old('price', $package->price ?? '') }}" required>
                        @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Orden</label>
                        <input class="form-control @error('sort_order') is-invalid @enderror" name="sort_order" type="number" min="0" value="{{ old('sort_order', $package->sort_order ?? 0) }}">
                        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $package->is_active ?? true))>
                            <span class="form-check-label">Activo</span>
                        </label>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descripcion comercial</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description', $package->description ?? '') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">QR de pago</label>
                        <input class="form-control @error('payment_qr') is-invalid @enderror" name="payment_qr" type="file" accept="image/jpeg,image/png,image/webp">
                        @error('payment_qr')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @if ($package?->payment_qr_url)
                            <a class="small d-inline-block mt-2" href="{{ $package->payment_qr_url }}" target="_blank" rel="noopener">Ver QR actual</a>
                        @endif
                    </div>
                    <div class="col-12 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">{{ $package ? 'Actualizar' : 'Crear' }}</button>
                        @if ($package)
                            <a class="btn btn-outline-secondary" href="{{ route('credit-packages.index') }}">Cancelar</a>
                        @endif
                    </div>
                </div>
            </x-ui.form-panel>
        </div>

        <div class="col-lg-7">
            <x-ui.table-card title="Paquetes">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead><tr><th>Paquete</th><th class="text-end">Creditos</th><th class="text-end">Precio</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($packages as $item)
                            <tr>
                                <td><div class="fw-semibold">{{ $item->name }}</div><div class="text-body-secondary small">{{ $item->description ?: '-' }}</div></td>
                                <td class="text-end">{{ $item->credits_amount }}</td>
                                <td class="text-end">USD {{ number_format((float) $item->price, 2) }}</td>
                                <td><span class="badge text-bg-{{ $item->is_active ? 'success' : 'secondary' }}">{{ $item->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="text-end">
                                    <a class="btn btn-outline-primary btn-sm" href="{{ route('credit-packages.edit', $item) }}">Editar</a>
                                    <form class="d-inline" method="POST" action="{{ route('credit-packages.destroy', $item) }}" data-confirm-delete="Eliminar paquete?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm" type="submit" @disabled($item->purchase_requests_count > 0)>Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-body-secondary py-3" colspan="5">No hay paquetes creados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-ui.table-card>
        </div>
    </div>
@endsection
