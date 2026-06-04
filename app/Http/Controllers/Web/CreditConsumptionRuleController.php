<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CreditConsumptionRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditConsumptionRuleController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        return view('credits.rules.index', [
            'rules' => CreditConsumptionRule::query()->orderBy('min_tour_price')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $data = $request->validate([
            'min_tour_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'max_tour_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'credits_required' => ['required', 'integer', 'min:1', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        CreditConsumptionRule::query()->create($data);

        return back()->with('success', 'Regla creada en USD.');
    }

    public function update(Request $request, CreditConsumptionRule $rule): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $data = $request->validate([
            'min_tour_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'max_tour_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'credits_required' => ['required', 'integer', 'min:1', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $rule->update($data);

        return back()->with('success', 'Regla actualizada.');
    }

    public function destroy(Request $request, CreditConsumptionRule $rule): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $rule->delete();

        return back()->with('success', 'Regla eliminada.');
    }
}
