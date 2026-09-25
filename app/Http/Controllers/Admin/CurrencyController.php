<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CurrencyController extends Controller
{
    public function index()
    {
        Gate::authorize('manage-currencies');

        $currencies = Currency::orderBy('is_default', 'desc')->orderBy('code')->get();

        return view('admin.currencies.index', compact('currencies'));
    }

    public function update(Request $request)
    {
        Gate::authorize('manage-currencies');

        $validated = $request->validate([
            'currencies' => 'required|array',
            'currencies.*.id' => 'required|exists:currencies,id',
            'currencies.*.exchange_rate' => 'required|numeric|min:0.0001',
            'currencies.*.is_active' => 'boolean',
        ]);

        foreach ($validated['currencies'] as $data) {
            Currency::where('id', $data['id'])->update([
                'exchange_rate' => $data['exchange_rate'],
                'is_active' => $data['is_active'] ?? false,
            ]);
        }

        CurrencyService::clearCache();

        return redirect()->route('admin.currencies.index')
            ->with('success', 'Tasas de cambio actualizadas.');
    }
}
