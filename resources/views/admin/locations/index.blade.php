@extends('admin.layout')

@section('content')
<div class="header">
    <div>
        <h2>Locations Management</h2>
        <p class="muted">Manage states, districts, cities, and areas.</p>
    </div>
</div>

@if(session('status'))
    <div class="status">{{ session('status') }}</div>
@endif

<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
    <!-- States -->
    <div class="card">
        <h3 style="margin-bottom: 16px;">States</h3>
        
        <form method="POST" action="{{ route('admin.locations.states.store') }}" style="margin-bottom: 16px;">
            @csrf
            <input type="text" name="name" placeholder="State name" required>
            <button type="submit" class="btn btn-primary">Add State</button>
        </form>

        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Cities</th>
                <th>Areas</th>
                <th>Districts</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($states as $state)
                <tr>
                    <td>{{ $state->name }}</td>
                    <td>{{ $state->cities_count }}</td>
                    <td>{{ $state->areas_count }}</td>
                    <td>{{ $state->districts_count }}</td>
                    <td class="actions">
                        <button class="btn" onclick="editState({{ $state->id }}, '{{ $state->name }}')">Edit</button>
                        <form method="POST" action="{{ route('admin.locations.states.delete', $state) }}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn" style="background: #ef4444;" onclick="return confirm('Delete this state?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No states yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Districts -->
    <div class="card">
        <h3 style="margin-bottom: 16px;">Districts</h3>
        
        <form method="POST" action="{{ route('admin.locations.districts.store') }}" style="margin-bottom: 16px;">
            @csrf
            <select name="state_id" required>
                <option value="">Select State</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                @endforeach
            </select>
            <input type="text" name="name" placeholder="District name" required>
            <button type="submit" class="btn btn-primary">Add District</button>
        </form>

        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>State</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($districts as $district)
                <tr>
                    <td>{{ $district->name }}</td>
                    <td>{{ $district->state->name }}</td>
                    <td class="actions">
                        <button class="btn" onclick="editDistrict({{ $district->id }}, {{ $district->state_id }}, '{{ $district->name }}')">Edit</button>
                        <form method="POST" action="{{ route('admin.locations.districts.delete', $district) }}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn" style="background: #ef4444;" onclick="return confirm('Delete this district?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No districts yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Cities/Town -->
    <div class="card">
        <h3 style="margin-bottom: 16px;">Cities/Town</h3>
        
        <form method="POST" action="{{ route('admin.locations.cities.store') }}" style="margin-bottom: 16px;">
            @csrf
            <select name="district_id" required>
                <option value="">Select District</option>
                @foreach($districts as $district)
                    <option value="{{ $district->id }}">{{ $district->name }} ({{ $district->state->name }})</option>
                @endforeach
            </select>
            <input type="text" name="name" placeholder="City name" required>
            <button type="submit" class="btn btn-primary">Add City</button>
        </form>

        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>District</th>
                <th>State</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($cities as $city)
                <tr>
                    <td>{{ $city->name }}</td>
                    <td>{{ $city->district?->name ?? 'Not mapped' }}</td>
                    <td>{{ $city->state->name }}</td>
                    <td class="actions">
                        <button class="btn" onclick="editCity({{ $city->id }}, {{ $city->district_id ?? 'null' }}, '{{ $city->name }}')">Edit</button>
                        <form method="POST" action="{{ route('admin.locations.cities.delete', $city) }}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn" style="background: #ef4444;" onclick="return confirm('Delete this city?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No cities yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <!-- Areas -->
    <div class="card">
        <h3 style="margin-bottom: 16px;">Areas</h3>
        
        <form method="POST" action="{{ route('admin.locations.areas.store') }}" style="margin-bottom: 16px;">
            @csrf
            <select name="city_id" id="areaCitySelect" onchange="syncAreaDistrict('areaCitySelect', 'areaDistrictInput', 'areaDistrictHint')" required>
                <option value="">Select City/Town</option>
                @foreach($cities as $city)
                    <option
                        value="{{ $city->id }}"
                        data-district-id="{{ $city->district_id ?? '' }}"
                        data-district-name="{{ $city->district?->name ?? 'Not mapped' }}"
                    >{{ $city->name }} ({{ $city->district?->name ?? 'Not mapped' }})</option>
                @endforeach
            </select>
            <input type="hidden" name="district_id" id="areaDistrictInput">
            <p id="areaDistrictHint" class="muted" style="font-size: 12px; margin-top: -8px; margin-bottom: 12px;">Area is mapped to the selected City/Town. District is derived automatically.</p>
            <input type="text" name="name" placeholder="Area name" required>
            <button type="submit" class="btn btn-primary">Add Area</button>
        </form>

        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>City/Town</th>
                <th>District</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($areas as $area)
                <tr>
                    <td>{{ $area->name }}</td>
                    <td>{{ $area->city->name }}</td>
                    <td>{{ $area->district->name }}</td>
                    <td class="actions">
                        <button class="btn" onclick="editArea({{ $area->id }}, {{ $area->city_id }}, {{ $area->district_id }}, '{{ $area->district->name }}', '{{ $area->name }}')">Edit</button>
                        <form method="POST" action="{{ route('admin.locations.areas.delete', $area) }}" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button class="btn" style="background: #ef4444;" onclick="return confirm('Delete this area?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No areas yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Edit State Modal -->
<div id="editStateModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 12px; min-width: 400px;">
        <h3>Edit State</h3>
        <form id="editStateForm" method="POST">
            @csrf
            @method('PUT')
            <input type="text" id="editStateName" name="name" required>
            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" class="btn" onclick="closeModal('editStateModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit City Modal -->
<div id="editCityModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 12px; min-width: 400px;">
        <h3>Edit City</h3>
        <form id="editCityForm" method="POST">
            @csrf
            @method('PUT')
            <select id="editCityDistrict" name="district_id" required>
                <option value="">Select District</option>
                @foreach($districts as $district)
                    <option value="{{ $district->id }}">{{ $district->name }} ({{ $district->state->name }})</option>
                @endforeach
            </select>
            <input type="text" id="editCityName" name="name" required>
            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" class="btn" onclick="closeModal('editCityModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit District Modal -->
<div id="editDistrictModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 12px; min-width: 400px;">
        <h3>Edit District</h3>
        <form id="editDistrictForm" method="POST">
            @csrf
            @method('PUT')
            <select id="editDistrictState" name="state_id" required>
                @foreach($states as $state)
                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                @endforeach
            </select>
            <input type="text" id="editDistrictName" name="name" required>
            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" class="btn" onclick="closeModal('editDistrictModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Area Modal -->
<div id="editAreaModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 12px; min-width: 400px;">
        <h3>Edit Area</h3>
        <form id="editAreaForm" method="POST">
            @csrf
            @method('PUT')
            <select id="editAreaCity" name="city_id" onchange="syncAreaDistrict('editAreaCity', 'editAreaDistrict', 'editAreaDistrictHint')" required>
                @foreach($cities as $city)
                    <option
                        value="{{ $city->id }}"
                        data-district-id="{{ $city->district_id ?? '' }}"
                        data-district-name="{{ $city->district?->name ?? 'Not mapped' }}"
                    >{{ $city->name }} ({{ $city->district?->name ?? 'Not mapped' }})</option>
                @endforeach
            </select>
            <input type="hidden" id="editAreaDistrict" name="district_id" required>
            <p id="editAreaDistrictHint" class="muted" style="font-size: 12px; margin-top: -8px; margin-bottom: 12px;">Area is mapped to the selected City/Town. District is derived automatically.</p>
            <input type="text" id="editAreaName" name="name" required>
            <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button type="submit" class="btn btn-primary">Update</button>
                <button type="button" class="btn" onclick="closeModal('editAreaModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editState(id, name) {
    document.getElementById('editStateForm').action = `/admin/locations/states/${id}`;
    document.getElementById('editStateName').value = name;
    document.getElementById('editStateModal').style.display = 'block';
}

function editCity(id, districtId, name) {
    document.getElementById('editCityForm').action = `/admin/locations/cities/${id}`;
    document.getElementById('editCityDistrict').value = districtId ?? '';
    document.getElementById('editCityName').value = name;
    document.getElementById('editCityModal').style.display = 'block';
}

function editDistrict(id, stateId, name) {
    document.getElementById('editDistrictForm').action = `/admin/locations/districts/${id}`;
    document.getElementById('editDistrictState').value = stateId;
    document.getElementById('editDistrictName').value = name;
    document.getElementById('editDistrictModal').style.display = 'block';
}

function syncAreaDistrict(citySelectId, districtInputId, hintId, fallbackDistrictId = '', fallbackDistrictName = '') {
    const citySelect = document.getElementById(citySelectId);
    const districtInput = document.getElementById(districtInputId);
    const hint = document.getElementById(hintId);
    const selectedOption = citySelect?.options[citySelect.selectedIndex];
    const districtId = selectedOption?.dataset.districtId || fallbackDistrictId || '';
    const districtName = selectedOption?.dataset.districtName || fallbackDistrictName || 'Not mapped';

    if (districtInput) {
        districtInput.value = districtId;
    }

    if (hint) {
        hint.textContent = districtId
            ? `Mapped District: ${districtName}`
            : '';
        hint.style.color = districtId ? '' : '#b45309';
    }
}

function editArea(id, cityId, districtId, districtName, name) {
    document.getElementById('editAreaForm').action = `/admin/locations/areas/${id}`;
    document.getElementById('editAreaCity').value = cityId;
    document.getElementById('editAreaName').value = name;
    syncAreaDistrict('editAreaCity', 'editAreaDistrict', 'editAreaDistrictHint', districtId, districtName);
    document.getElementById('editAreaModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    syncAreaDistrict('areaCitySelect', 'areaDistrictInput', 'areaDistrictHint');
});

</script>
@endsection
