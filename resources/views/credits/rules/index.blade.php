@extends('layouts.admin')

@section('title', 'Reglas de Consumo | '.config('app.name', 'Base Admin'))
@section('page-title', 'Reglas de Consumo')
@section('page-subtitle', 'Creditos requeridos segun precio del tour en USD')

@section('content')
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <x-ui.form-panel :action="route('credit-consumption-rules.store')" title="Nueva regla USD">
        <div class="row g-3 align-items-end">
            <div class="col-md-3"><label class="form-label">Desde USD</label><input class="form-control" name="min_tour_price" type="number" min="0" step="0.01" required></div>
            <div class="col-md-3"><label class="form-label">Hasta USD</label><input class="form-control" name="max_tour_price" type="number" min="0" step="0.01" placeholder="Sin limite"></div>
            <div class="col-md-3"><label class="form-label">Creditos</label><input class="form-control" name="credits_required" type="number" min="1" required></div>
            <div class="col-md-2"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><span class="form-check-label">Activa</span></label></div>
            <div class="col-md-1"><button class="btn btn-primary w-100" type="submit"><i class="ti ti-plus"></i></button></div>
        </div>
    </x-ui.form-panel>

    <x-ui.table-card title="Reglas actuales" class="mt-3">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead><tr><th>Rango USD</th><th class="text-end">Creditos</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
            <tbody>
                @forelse ($rules as $rule)
                    <tr>
                        <form method="POST" action="{{ route('credit-consumption-rules.update', $rule) }}">
                            @csrf @method('PUT')
                            <td class="d-flex gap-2">
                                <input class="form-control form-control-sm" name="min_tour_price" type="number" min="0" step="0.01" value="{{ $rule->min_tour_price }}" required>
                                <input class="form-control form-control-sm" name="max_tour_price" type="number" min="0" step="0.01" value="{{ $rule->max_tour_price }}" placeholder="Sin limite">
                            </td>
                            <td class="text-end"><input class="form-control form-control-sm text-end" name="credits_required" type="number" min="1" value="{{ $rule->credits_required }}" required></td>
                            <td><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($rule->is_active)><span class="form-check-label">Activa</span></label></td>
                            <td class="text-end">
                                <button class="btn btn-outline-primary btn-sm" type="submit">Guardar</button>
                        </form>
                                <form class="d-inline" method="POST" action="{{ route('credit-consumption-rules.destroy', $rule) }}" data-confirm-delete="Eliminar regla?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Eliminar</button>
                                </form>
                            </td>
                    </tr>
                @empty
                    <tr><td class="text-center text-body-secondary py-3" colspan="4">No hay reglas configuradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-ui.table-card>
@endsection
