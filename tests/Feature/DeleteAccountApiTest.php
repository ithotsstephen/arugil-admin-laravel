<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\MobileUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteAccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_delete_their_account(): void
    {
        $user = User::factory()->create([
            'phone' => '9999999999',
        ]);
        $category = Category::create(['name' => 'Services']);

        Business::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Delete Me Business',
        ]);

        MobileUser::create([
            'full_name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('api');

        Sanctum::actingAs($user, [], 'sanctum');

        $response = $this->deleteJson('/api/v1/auth/account', [
            'current_password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Account deleted successfully.',
            ]);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('mobile_users', ['email' => $user->email]);
        $this->assertDatabaseMissing('businesses', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_password_user_must_confirm_current_password_before_deleting_account(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, [], 'sanctum');

        $response = $this->deleteJson('/api/v1/auth/account', [
            'current_password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_social_user_can_delete_account_without_password_confirmation(): void
    {
        $user = User::factory()->create([
            'provider' => 'google',
            'provider_id' => 'google-123',
        ]);

        Sanctum::actingAs($user, [], 'sanctum');

        $response = $this->deleteJson('/api/v1/auth/account');

        $response->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}