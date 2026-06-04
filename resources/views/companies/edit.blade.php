@extends('layouts.admin')

@php($isIndependentRegistration = $company->registrationRequest?->type === \App\Models\RegistrationRequest::TYPE_INDEPENDENT)

@section('title', ($isIndependentRegistration ? 'Editar perfil comercial' : 'Editar empresa').' | '.config('app.name', 'Base Admin'))
@section('page-title', $isIndependentRegistration ? 'Editar perfil comercial' : 'Editar empresa')
@section('content')
    <x-ui.form-panel :title="$isIndependentRegistration ? 'Datos de independiente' : 'Datos de empresa'">
        @include('companies.partials.edit-form')
    </x-ui.form-panel>
@endsection
