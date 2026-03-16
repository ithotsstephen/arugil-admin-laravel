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
            // radius input is in kilometers from the UI; convert to meters for storage
            'radius' => ['nullable', 'numeric', 'min:0'],
        ]);

        $meters = isset($data['radius']) && $data['radius'] !== '' ? (int) round(floatval($data['radius']) * 1000) : null;

        $setting = GeofenceSetting::first();

        if (! $setting) {
            GeofenceSetting::create(['radius' => $meters]);
        } else {
            $setting->update(['radius' => $meters]);
        }

        return redirect()->route('admin.geofence.index')->with('status', 'Geofence updated.');
    }
}
