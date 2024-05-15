<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\JsonResponse;

class AuthTest extends TestCase
{
    use WithFaker;

    /**
     * A basic test example.
     */
    public function test_that_user_can_register(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => $this->faker->unique()->safeEmail,
            'bvn' => '12345678901',
            'phone' => '08012345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated();
    }

    public function test_that_user_can_login()
    {
        // Create a user for testing
        /** @var User $user */
        $user = User::factory()->create([
            'password' => bcrypt('password'),
        ]);

        // Attempt to login with the user's credentials
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // Assert that the login request was successful
        $response->assertOk();
        // Assert that the response contains the access token
        $response->assertJsonStructure([
            'access_token',
            'token_type',
        ]);

        return $response;
    }

    public function test_that_user_cannot_register_with_invalid_data(): void
    {
        // Attempt to register with invalid data
        $response = $this->postJson('/api/register', [
            // Missing required fields
        ]);

        // Assert that the request returns a validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'name',
            'email',
            'bvn',
            'password'
        ]);
    }

    public function test_that_user_cannot_register_with_existing_email(): void
    {
        // Create a user for testing
        /** @var User $user */
        $user = User::factory()->create();

        // Attempt to register with the same email as the existing user
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => $user->email,
            'bvn' => '12345678901',
            'phone' => '08012345678',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Assert that the request returns a validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    public function test_that_user_can_logout(): void
    {
        // Create a user for testing
        /** @var User $user */
        $user = User::factory()->create();
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // fill the sanctum token
        $token = $response->json('access_token');

        // Attempt to logout
        $response = $this->withHeader('Authorization', "Bearer $token")->postJson('/api/logout');

        // Assert that the logout request was successful
        $response->assertOk();
        // Assert that the user cannot access protected routes
        $this->getJson('/api/user')->assertUnauthorized();

    }



}
