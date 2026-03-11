<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\GeofenceSetting;
use Illuminate\Http\Request;

class GeoFencingController extends Controller
{
    public function index(Request $request)
    {
        $setting = GeofenceSetting::first();

        return view('admin.geofence.index', compact('setting'));
    }
    public function update(Request $request)
    {
        $data = $request->validate([
            'radius' => ['nullable', 'integer', 'min:0'],
        ]);

        $setting = GeofenceSetting::first();

        if (! $setting) {
            GeofenceSetting::create(['radius' => $data['radius'] ?? null]);
        } else {
            $setting->update(['radius' => $data['radius'] ?? null]);
        }

        return redirect()->route('admin.geofence.index')->with('status', 'Geofence updated.');
    }
}
