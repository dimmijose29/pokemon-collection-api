<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => 'Italia',
            'preferred_currency' => 'EUR',
            'birth_year' => 1994,
            'avatar_url' => 'https://example.com/avatar.jpg',
            'bio' => 'Test bio',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['user', 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'username' => 'testuser',
            'is_admin' => false,
        ]);
    }

    /** @test */
    public function user_can_login_with_email_or_username()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);

        // Login con email
        $this->postJson('/api/auth/login', [
            'login' => 'test@example.com',
            'password' => 'password123',
        ])->assertStatus(200)
        ->assertJsonStructure(['user', 'token']);

        // Login con username
        $this->postJson('/api/auth/login', [
            'login' => 'testuser',
            'password' => 'password123',
        ])->assertStatus(200)
        ->assertJsonStructure(['user', 'token']);
    }

    // Aggiungi test per 2FA, logout, ecc.
}