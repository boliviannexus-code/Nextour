<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CreditPackage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CreditPackageController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return view('credits.packages.index', [
            'packages' => CreditPackage::query()->withCount('purchaseRequests')->orderBy('sort_order')->orderBy('name')->get(),
            'package' => null,
        ]);
    }

    public function edit(Request $request, CreditPackage $package): View
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return view('credits.packages.index', [
            'packages' => CreditPackage::query()->withCount('purchaseRequests')->orderBy('sort_order')->orderBy('name')->get(),
            'package' => $package,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $data = $this->validated($request);
        $data['currency'] = CreditPackage::CURRENCY_USD;
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($request->hasFile('payment_qr')) {
            $data['payment_qr_path'] = $request->file('payment_qr')->store('credits/packages/qr', 'public');
        }

        CreditPackage::query()->create($data);

        return redirect()->route('credit-packages.index')->with('success', 'Paquete creado en USD.');
    }

    public function update(Request $request, CreditPackage $package): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $data = $this->validated($request);
        $data['currency'] = CreditPackage::CURRENCY_USD;
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        if ($request->hasFile('payment_qr')) {
            if ($package->payment_qr_path) {
                Storage::disk('public')->delete($package->payment_qr_path);
            }

            $data['payment_qr_path'] = $request->file('payment_qr')->store('credits/packages/qr', 'public');
        }

        $package->update($data);

        return redirect()->route('credit-packages.index')->with('success', 'Paquete actualizado.');
    }

    public function destroy(Request $request, CreditPackage $package): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        if ($package->purchaseRequests()->exists()) {
            return back()->withErrors(['package' => 'No se puede eliminar un paquete con compras asociadas.']);
        }

        if ($package->payment_qr_path) {
            Storage::disk('public')->delete($package->payment_qr_path);
        }

        $package->delete();

        return redirect()->route('credit-packages.index')->with('success', 'Paquete eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'credits_amount' => ['required', 'integer', 'min:1', 'max:1000000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'payment_qr' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
    }
}
