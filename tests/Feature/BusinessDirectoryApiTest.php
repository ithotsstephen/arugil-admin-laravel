<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\District;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessDirectoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_districts_for_the_dropdown(): void
    {
        $kerala = State::create(['name' => 'Kerala']);
        $tamilNadu = State::create(['name' => 'Tamil Nadu']);

        District::create(['state_id' => $kerala->id, 'name' => 'Thrissur']);
        District::create(['state_id' => $kerala->id, 'name' => 'Ernakulam']);
        District::create(['state_id' => $tamilNadu->id, 'name' => 'Chennai']);

        $response = $this->getJson('/api/v1/districts');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'Chennai')
            ->assertJsonPath('data.1.name', 'Ernakulam')
            ->assertJsonPath('data.1.state', 'Kerala')
            ->assertJsonPath('data.2.name', 'Thrissur');
    }

    public function test_it_lists_areas_for_a_selected_district(): void
    {
        [$district, $city] = $this->createLocation();
        $otherDistrict = District::create(['state_id' => $district->state_id, 'name' => 'Thrissur']);

        Area::create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'name' => 'Edappally',
            'pincode' => '682024',
        ]);
        Area::create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'name' => 'Aluva',
            'pincode' => '683101',
        ]);
        Area::create([
            'city_id' => $city->id,
            'district_id' => $otherDistrict->id,
            'name' => 'Kodungallur',
            'pincode' => '680664',
        ]);

        $response = $this->getJson("/api/v1/districts/{$district->id}/areas");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Aluva')
            ->assertJsonPath('data.1.name', 'Edappally')
            ->assertJsonMissing(['name' => 'Kodungallur']);
    }

    public function test_it_lists_area_businesses_with_category_and_subcategory_filters(): void
    {
        [$district, $city] = $this->createLocation();
        $area = Area::create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'name' => 'Aluva',
            'pincode' => '683101',
        ]);
        $otherArea = Area::create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'name' => 'Edappally',
            'pincode' => '682024',
        ]);

        $owner = User::factory()->create();
        $services = Category::create(['name' => 'Services']);
        $plumbing = Category::create(['name' => 'Plumbing', 'parent_id' => $services->id]);
        $electrical = Category::create(['name' => 'Electrical', 'parent_id' => $services->id]);
        $retail = Category::create(['name' => 'Retail']);

        Business::create([
            'user_id' => $owner->id,
            'category_id' => $plumbing->id,
            'district_id' => $district->id,
            'area_id' => $area->id,
            'name' => 'Alpha Plumbing',
            'is_approved' => true,
        ]);
        Business::create([
            'user_id' => $owner->id,
            'category_id' => $electrical->id,
            'district_id' => $district->id,
            'area_id' => $area->id,
            'name' => 'Zulu Electrical',
            'is_approved' => true,
        ]);
        Business::create([
            'user_id' => $owner->id,
            'category_id' => $retail->id,
            'district_id' => $district->id,
            'area_id' => $otherArea->id,
            'name' => 'Outside Retail',
            'is_approved' => true,
        ]);

        $categoryResponse = $this->getJson("/api/v1/areas/{$area->id}/businesses?category_id={$services->id}");

        $categoryResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Plumbing')
            ->assertJsonPath('data.1.name', 'Zulu Electrical')
            ->assertJsonPath('meta.total', 2);

        $subcategoryResponse = $this->getJson(
            "/api/v1/areas/{$area->id}/businesses?category_id={$services->id}&subcategory_id={$plumbing->id}"
        );

        $subcategoryResponse
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Plumbing')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissing(['name' => 'Zulu Electrical']);
    }

    public function test_it_searches_businesses_across_related_location_fields_and_prioritizes_name_matches(): void
    {
        [$district, $city] = $this->createLocation();
        $plumbingArea = Area::create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'name' => 'Plumbing Nagar',
            'pincode' => '682001',
        ]);
        $marketArea = Area::create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'name' => 'Market Road',
            'pincode' => '682002',
        ]);

        $owner = User::factory()->create();
        $services = Category::create(['name' => 'Services']);
        $plumbing = Category::create(['name' => 'Plumbing', 'parent_id' => $services->id]);

        Business::create([
            'user_id' => $owner->id,
            'category_id' => $services->id,
            'district_id' => $district->id,
            'area_id' => $plumbingArea->id,
            'name' => 'Ordinary Store',
            'is_approved' => true,
        ]);
        Business::create([
            'user_id' => $owner->id,
            'category_id' => $plumbing->id,
            'district_id' => $district->id,
            'area_id' => $marketArea->id,
            'name' => 'Plumbing Pros',
            'owner_name' => 'Alex Thomas',
            'keywords' => ['pipes', 'repair'],
            'is_approved' => true,
        ]);

        $response = $this->getJson('/api/v1/businesses/search?q=plumbing');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Plumbing Pros')
            ->assertJsonPath('data.1.name', 'Ordinary Store')
            ->assertJsonPath('meta.total', 2);
    }

    private function createLocation(): array
    {
        $state = State::create(['name' => 'Kerala']);
        $city = City::create(['state_id' => $state->id, 'name' => 'Kochi']);
        $district = District::create(['state_id' => $state->id, 'name' => 'Ernakulam']);

        return [$district, $city];
    }
}