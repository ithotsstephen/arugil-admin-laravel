<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Area;
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
            ])
            ->map(function (Area $area) {
                return [
                    'id' => $area->id,
                    'name' => $area->name,
                    'district_id' => $area->district_id,
                    'is_all' => false,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => collect([[
                'id' => 'all',
                'name' => 'All areas',
                'district_id' => $district->id,
                'is_all' => true,
            ]])->concat($areas)->values(),
        ]);
    }
}