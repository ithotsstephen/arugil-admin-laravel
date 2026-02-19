<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\City;
use App\Models\District;
use App\Models\Area;
use Illuminate\Http\Request;

class LocationsController extends Controller
{
    public function index()
    {
        $states = State::withCount(['cities', 'areas', 'districts', 'businesses'])->orderBy('name')->get();
        $cities = City::with('state')->withCount(['businesses'])->orderBy('name')->get();
        $areas = Area::with(['city.state', 'district'])->withCount(['businesses'])->orderBy('name')->get();
        $districts = District::with('state')->withCount('businesses')->orderBy('name')->get();

        return view('admin.locations.index', compact('states', 'cities', 'districts', 'areas'));
    }

    // State CRUD
    public function storeState(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:states,name'],
        ]);

        State::create($data);

        return redirect()->route('admin.locations.index')->with('status', 'State added successfully.');
    }

    public function updateState(Request $request, State $state)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:states,name,' . $state->id],
        ]);

        $state->update($data);

        return redirect()->route('admin.locations.index')->with('status', 'State updated successfully.');
    }

    public function deleteState(State $state)
    {
        $state->delete();

        return redirect()->route('admin.locations.index')->with('status', 'State deleted successfully.');
    }

    // City CRUD
    public function storeCity(Request $request)
    {
        $data = $request->validate([
            'state_id' => ['required', 'exists:states,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        City::create($data);

        return redirect()->route('admin.locations.index')->with('status', 'City added successfully.');
    }

    public function updateCity(Request $request, City $city)
    {
        $data = $request->validate([
            'state_id' => ['required', 'exists:states,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $city->update($data);

        return redirect()->route('admin.locations.index')->with('status', 'City updated successfully.');
    }

    public function deleteCity(City $city)
    {
        $city->delete();

        return redirect()->route('admin.locations.index')->with('status', 'City deleted successfully.');
    }

    // District CRUD
    public function storeDistrict(Request $request)
    {
        $data = $request->validate([
            'state_id' => ['required', 'exists:states,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        District::create($data);

        return redirect()->route('admin.locations.index')->with('status', 'District added successfully.');
    }

    public function updateDistrict(Request $request, District $district)
    {
        $data = $request->validate([
            'state_id' => ['required', 'exists:states,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $district->update($data);

        return redirect()->route('admin.locations.index')->with('status', 'District updated successfully.');
    }

    public function deleteDistrict(District $district)
    {
        $district->delete();

        return redirect()->route('admin.locations.index')->with('status', 'District deleted successfully.');
    }

    // Area CRUD
    public function storeArea(Request $request)
    {
        $data = $request->validate([
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'name' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'string', 'max:20'],
        ]);

        Area::create($data);

        return redirect()->route('admin.locations.index')->with('status', 'Area added successfully.');
    }

    public function updateArea(Request $request, Area $area)
    {
        $data = $request->validate([
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'name' => ['required', 'string', 'max:255'],
            'pincode' => ['required', 'string', 'max:20'],
        ]);

        $area->update($data);

        return redirect()->route('admin.locations.index')->with('status', 'Area updated successfully.');
    }

    public function deleteArea(Area $area)
    {
        $area->delete();

        return redirect()->route('admin.locations.index')->with('status', 'Area deleted successfully.');
    }

    // AJAX endpoints for cascading dropdowns
    public function getCities(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($cities);
    }

    public function getDistricts(Request $request)
    {
        $districts = District::where('state_id', $request->state_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($districts);
    }

    public function getAreas(Request $request)
    {
        $areasQuery = Area::query();

        if ($request->filled('city_id')) {
            $areasQuery->where('city_id', $request->city_id);
        }

        if ($request->filled('district_id')) {
            $areasQuery->where('district_id', $request->district_id);
        }

        $areas = $areasQuery
            ->orderBy('name')
            ->get(['id', 'name', 'pincode']);

        return response()->json($areas);
    }
}

