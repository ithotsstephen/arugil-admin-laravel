<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EmergencyNumber;

class EmergencyController extends Controller
{
    public function index()
    {
        $numbers = EmergencyNumber::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return response()->json($numbers);
    }
}
