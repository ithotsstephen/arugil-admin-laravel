<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBusinessProductUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_add_and_delete_business_products_while_editing_a_business(): void
    {
        $manager = User::factory()->create([
            'role' => 'manager',
        ]);

        $category = Category::create([
            'name' => 'Retail',
        ]);

        $business = Business::create([
            'user_id' => $manager->id,
            'category_id' => $category->id,
            'name' => 'Test Shop',
        ]);

        $updatedProduct = $business->products()->create([
            'name' => 'Old Product',
            'price' => '99',
            'description' => 'Old description',
            'image_url' => 'https://example.com/old-product.jpg',
        ]);

        $deletedProduct = $business->products()->create([
            'name' => 'Delete Me',
            'price' => '10',
            'description' => 'To be removed',
            'image_url' => 'https://example.com/delete-product.jpg',
        ]);

        $response = $this->actingAs($manager)->put(route('admin.businesses.update', $business), [
            'name' => 'Test Shop',
            'category_id' => $category->id,
            'existing_products' => [
                [
                    'id' => $updatedProduct->id,
                    'name' => 'Updated Product',
                    'price' => '149',
                    'description' => 'Updated description',
                ],
            ],
            'products' => [
                [
                    'existing_id' => $updatedProduct->id,
                    'image_url' => 'https://example.com/updated-product.jpg',
                ],
                [
                    'name' => 'New Product',
                    'price' => '199',
                    'description' => 'New description',
                    'image_url' => 'https://example.com/new-product.jpg',
                ],
            ],
            'delete_products' => [$deletedProduct->id],
        ]);

        $response->assertRedirect(route('admin.businesses.index'));

        $this->assertDatabaseHas('business_products', [
            'id' => $updatedProduct->id,
            'business_id' => $business->id,
            'name' => 'Updated Product',
            'price' => '149',
            'description' => 'Updated description',
            'image_url' => 'https://example.com/updated-product.jpg',
        ]);

        $this->assertDatabaseHas('business_products', [
            'business_id' => $business->id,
            'name' => 'New Product',
            'price' => '199',
            'description' => 'New description',
            'image_url' => 'https://example.com/new-product.jpg',
        ]);

        $this->assertDatabaseMissing('business_products', [
            'id' => $deletedProduct->id,
        ]);
    }
}