<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyNumber;
use Illuminate\Http\Request;

class EmergencyController extends Controller
{
    public function index()
    {
        $numbers = EmergencyNumber::query()
            ->orderBy('category')
            ->paginate(20);

        return view('admin.emergency.index', compact('numbers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
        ]);

        EmergencyNumber::create($data);

        return redirect()->back()->with('status', 'Emergency number added.');
    }

    public function destroy(EmergencyNumber $emergency)
    {
        $emergency->delete();

        return redirect()->back()->with('status', 'Emergency number deleted.');
    }
}
