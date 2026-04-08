<?php

namespace App\Http\Controllers\Vendor;

use App\Domains\Vendor\Models\Restaurant;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $restaurant = app('tenant');

        if (! ($restaurant instanceof Restaurant)) {
            abort(404, 'Restaurant model not found for this vendor.');
        }

        return view('vendor.settings.index', compact('restaurant'));
    }

    public function update(Request $request)
    {
        $restaurant = app('tenant');

        if (! ($restaurant instanceof Restaurant)) {
            abort(404, 'Restaurant model not found for this vendor.');
        }

        $data = $request->validate([
            'working_hours' => 'nullable|array',
            'working_hours.*.day' => 'required|string',
            'working_hours.*.start' => 'required',
            'working_hours.*.end' => 'required',
            'working_hours.*.is_day_off' => 'boolean',
            'delivery_zones' => 'nullable|string',
        ]);

        $settings = $restaurant->settings ?? [];
        $settings['working_hours'] = $data['working_hours'] ?? [];

        $restaurant->update([
            'settings' => $settings,
            'delivery_zones' => $data['delivery_zones'] ?? null,
        ]);

        return back()->with('success', 'Настройки сохранены');
    }
}
