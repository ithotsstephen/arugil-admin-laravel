<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\District;

class LocationController extends Controller
{
    public function districts()
    {
        $districts = District::query()
            ->leftJoin('states', 'states.id', '=', 'districts.state_id')
            ->orderBy('districts.name')
            ->get([
                'districts.id',
                'districts.name',
                'states.name as state',
            ]);

        return response()->json([
            'success' => true,
            'data' => $districts,
        ]);
    }

    public function areas(District $district)
    {
        $areas = $district->areas()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'district_id',
            ]);

        return response()->json([
            'success' => true,
            'data' => $areas,
        ]);
    }
}