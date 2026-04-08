<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\MobileUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeleteAccountWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_account_page_is_publicly_accessible(): void
    {
        $response = $this->get('/delete-account');

        $response
            ->assertOk()
            ->assertSee('Delete your account')
            ->assertSee('Delete Account');
    }

    public function test_user_can_delete_account_from_public_link_with_valid_credentials(): void
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

        $response = $this->post('/delete-account', [
            'login' => $user->email,
            'current_password' => 'password',
        ]);

        $response
            ->assertRedirect('/delete-account')
            ->assertSessionHas('status', 'Your account has been deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('mobile_users', ['email' => $user->email]);
        $this->assertDatabaseMissing('businesses', ['user_id' => $user->id]);
    }
}