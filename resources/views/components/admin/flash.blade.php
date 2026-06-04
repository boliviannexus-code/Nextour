@if (session('success'))
    <div data-laravel-alert data-alert-type="success" data-alert-title="Correcto" data-alert-message="{{ session('success') }}"></div>
@endif

@if (session('error'))
    <div data-laravel-alert data-alert-type="error" data-alert-title="Error" data-alert-message="{{ session('error') }}"></div>
@endif

@if (($errors ?? null)?->any())
    <div data-laravel-alert data-alert-type="error" data-alert-title="Atencion" data-alert-message="Revisa los datos ingresados."></div>
@endif
