<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'jane@example.com');

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
        ]);
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.email.0', 'The email has already been taken.');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'Password123!',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $user->email);
    }

    public function test_guest_cannot_fetch_their_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response
            ->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $user->createToken('browser');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout');

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/auth/profile', [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'updated@example.com')
            ->assertJsonPath('data.user.name', 'Updated Name');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_user_can_reset_their_password(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPassword123!',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/password/reset', [
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword456!',
                'password_confirmation' => 'NewPassword456!',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Password updated successfully.');

        $this->assertTrue(Hash::check('NewPassword456!', $user->fresh()->password));
    }

    public function test_user_policy_allows_self_and_denies_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertTrue($user->can('view', $user));
        $this->assertFalse($user->can('view', $otherUser));
    }
}
