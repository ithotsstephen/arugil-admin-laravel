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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
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
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', 'all')
            ->assertJsonPath('data.0.name', 'All areas')
            ->assertJsonPath('data.0.is_all', true)
            ->assertJsonPath('data.1.name', 'Aluva')
            ->assertJsonPath('data.1.is_all', false)
            ->assertJsonPath('data.2.name', 'Edappally')
            ->assertJsonMissing(['name' => 'Kodungallur']);
    }

    public function test_it_lists_district_businesses_when_all_areas_is_selected(): void
    {
        [$district, $city] = $this->createLocation();
        $otherDistrict = District::create(['state_id' => $district->state_id, 'name' => 'Thrissur']);
        $firstArea = Area::create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'name' => 'Aluva',
            'pincode' => '683101',
        ]);
        $secondArea = Area::create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'name' => 'Edappally',
            'pincode' => '682024',
        ]);
        $otherArea = Area::create([
            'city_id' => $city->id,
            'district_id' => $otherDistrict->id,
            'name' => 'Kodungallur',
            'pincode' => '680664',
        ]);

        $owner = User::factory()->create();
        $category = Category::create(['name' => 'Services']);

        Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'area_id' => $secondArea->id,
            'name' => 'Beta Services',
            'is_approved' => true,
        ]);
        Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $district->id,
            'area_id' => $firstArea->id,
            'name' => 'Alpha Services',
            'is_approved' => true,
        ]);
        Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'district_id' => $otherDistrict->id,
            'area_id' => $otherArea->id,
            'name' => 'Outside Services',
            'is_approved' => true,
        ]);

        $response = $this->getJson("/api/v1/areas/all/businesses?district_id={$district->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Services')
            ->assertJsonPath('data.1.name', 'Beta Services')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonMissing(['name' => 'Outside Services']);
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

    public function test_it_uses_openai_embeddings_to_semantically_rank_business_search_results(): void
    {
        config()->set('services.openai.api_key', 'test-openai-key');
        config()->set('services.openai.embedding_model', 'text-embedding-3-small');

        [$district, $city] = $this->createLocation();
        $area = Area::create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'name' => 'Town Center',
            'pincode' => '682003',
        ]);

        $owner = User::factory()->create();
        $services = Category::create(['name' => 'Services']);
        $plumbing = Category::create(['name' => 'Plumbing', 'parent_id' => $services->id]);
        $decor = Category::create(['name' => 'Decor', 'parent_id' => $services->id]);

        Business::create([
            'user_id' => $owner->id,
            'category_id' => $plumbing->id,
            'district_id' => $district->id,
            'city_id' => $city->id,
            'area_id' => $area->id,
            'name' => 'AquaFix Services',
            'description' => 'Pipe leak repair, drain cleaning and bathroom plumbing.',
            'services' => ['Leak repair', 'Pipe replacement'],
            'is_approved' => true,
        ]);

        Business::create([
            'user_id' => $owner->id,
            'category_id' => $decor->id,
            'district_id' => $district->id,
            'city_id' => $city->id,
            'area_id' => $area->id,
            'name' => 'Canvas Decor Studio',
            'description' => 'Interior styling and wall decor.',
            'is_approved' => true,
        ]);

        $this->fakeEmbeddingResponses();

        $response = $this->getJson('/api/v1/businesses/search?q=someone to fix a leaking kitchen sink');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'AquaFix Services')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_it_uses_openai_embeddings_to_semantically_rank_index_results_when_q_is_present(): void
    {
        config()->set('services.openai.api_key', 'test-openai-key');
        config()->set('services.openai.embedding_model', 'text-embedding-3-small');

        [$district, $city] = $this->createLocation();
        $area = Area::create([
            'city_id' => $city->id,
            'district_id' => $district->id,
            'name' => 'Town Center',
            'pincode' => '682003',
        ]);

        $owner = User::factory()->create();
        $services = Category::create(['name' => 'Services']);
        $plumbing = Category::create(['name' => 'Plumbing', 'parent_id' => $services->id]);
        $decor = Category::create(['name' => 'Decor', 'parent_id' => $services->id]);

        Business::create([
            'user_id' => $owner->id,
            'category_id' => $plumbing->id,
            'district_id' => $district->id,
            'city_id' => $city->id,
            'area_id' => $area->id,
            'name' => 'AquaFix Services',
            'description' => 'Pipe leak repair, drain cleaning and bathroom plumbing.',
            'services' => ['Leak repair', 'Pipe replacement'],
            'is_approved' => true,
        ]);

        Business::create([
            'user_id' => $owner->id,
            'category_id' => $decor->id,
            'district_id' => $district->id,
            'city_id' => $city->id,
            'area_id' => $area->id,
            'name' => 'Canvas Decor Studio',
            'description' => 'Interior styling and wall decor.',
            'is_approved' => true,
        ]);

        $this->fakeEmbeddingResponses();

        $response = $this->getJson('/api/v1/businesses?q=need a plumber for leaking pipe');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'AquaFix Services')
            ->assertJsonPath('meta.total', 2);
    }

    public function test_it_accepts_webp_images_when_creating_products(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $category = Category::create(['name' => 'Services']);
        $business = Business::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'name' => 'Alpha Services',
            'is_approved' => true,
        ]);

        $webpImage = UploadedFile::fake()->createWithContent(
            'pump.webp',
            base64_decode('UklGRiIAAABXRUJQVlA4TBEAAAAvAAAAAAfQ//73v/+BiOh/AAA=')
        );

        $response = $this->postJson("/api/v1/businesses/{$business->id}/products", [
            'name' => 'Pump Set',
            'image' => $webpImage,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('name', 'Pump Set');
    }

    private function createLocation(): array
    {
        $state = State::create(['name' => 'Kerala']);
        $city = City::create(['state_id' => $state->id, 'name' => 'Kochi']);
        $district = District::create(['state_id' => $state->id, 'name' => 'Ernakulam']);

        return [$district, $city];
    }

    private function fakeEmbeddingResponses(): void
    {
        Http::fake(function ($request) {
            $input = $request['input'];

            if (! is_array($input)) {
                $input = [$input];
            }

            $vectors = array_map(function ($text) {
                $lower = mb_strtolower((string) $text, 'UTF-8');

                if (str_contains($lower, 'leaking') || str_contains($lower, 'plumb') || str_contains($lower, 'pipe')) {
                    return [1.0, 0.0, 0.0];
                }

                if (str_contains($lower, 'decor') || str_contains($lower, 'interior') || str_contains($lower, 'wall')) {
                    return [0.0, 1.0, 0.0];
                }

                return [0.2, 0.2, 0.2];
            }, $input);

            return Http::response([
                'data' => array_map(
                    fn ($embedding, $index) => ['index' => $index, 'embedding' => $embedding],
                    $vectors,
                    array_keys($vectors)
                ),
            ]);
        });
    }
}