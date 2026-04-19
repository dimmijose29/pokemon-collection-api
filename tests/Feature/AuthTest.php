<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PragmaRX\Google2FALaravel\Google2FA;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => bcrypt('password123'),
        ]);
    }

    /** @test */
    public function user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'test2@example.com',
            'username' => 'testuser2',
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
            'email' => 'test2@example.com',
            'username' => 'testuser2',
            'is_admin' => false,
        ]);
    }

    /** @test */
    public function user_can_login_with_email_or_username()
    {
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

    // 2FA

    /** @test */
    public function user_can_enable_two_factor()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson('/api/auth/2fa/enable');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'secret',
                     'qr_code',
                 ]);

        $this->assertNotNull($response->json('secret'));
        $this->assertStringStartsWith('data:image/svg+xml;base64,', $response->json('qr_code'));

        // enable salva il secret ma NON attiva ancora la 2FA (serve confirm)
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'totp_secret' => $response->json('secret'),
            'is_2fa_enabled' => false,
        ]);
    }

    /** @test */
    public function user_can_confirm_two_factor_with_valid_code()
    {
        // Abilita 2FA
        $this->actingAs($this->user, 'sanctum');
        $enableResponse = $this->postJson('/api/auth/2fa/enable');
        $secret = $enableResponse->json('secret');

        // Genera codice valido (usa la stessa libreria per simulare)
        $google2fa = app(Google2FA::class);
        $code = $google2fa->getCurrentOtp($secret);

        $response = $this->postJson('/api/auth/2fa/confirm', [
            'code' => $code,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => '2FA confermato con successo',
                 ])
                 ->assertJsonStructure([
                     'message',
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'two_factor_confirmed_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /** @test */
    public function user_cannot_confirm_with_invalid_code()
    {
        $this->actingAs($this->user, 'sanctum');
        $this->postJson('/api/auth/2fa/enable');

        $response = $this->postJson('/api/auth/2fa/confirm', [
            'code' => '000000', // codice invalido
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'message' => 'Codice non valido',
                 ]);
    }

    /** @test */
    public function user_can_disable_two_factor()
    {
        $this->actingAs($this->user, 'sanctum');
        $enableResponse = $this->postJson('/api/auth/2fa/enable');
        $secret = $enableResponse->json('secret');

        $google2fa = app(Google2FA::class);
        $code = $google2fa->getCurrentOtp($secret);

        $response = $this->postJson('/api/auth/2fa/disable', [
            'code' => $code,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => '2FA disattivato',
                 ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_2fa_enabled' => false,
            'totp_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    /** @test */
    public function login_requires_two_factor_code_when_enabled()
    {
        // 1. Abilita e conferma 2FA
        $this->actingAs($this->user, 'sanctum');

        $enable = $this->postJson('/api/auth/2fa/enable');
        $secret = $enable->json('secret');

        $google2fa = app(\PragmaRX\Google2FALaravel\Google2FA::class);
        $validCode = $google2fa->getCurrentOtp($secret);

        $this->postJson('/api/auth/2fa/confirm', ['code' => $validCode])
            ->assertStatus(200);

        // 2. Logout per simulare un nuovo login
        $this->postJson('/api/auth/logout');

        // 3. Tenta login SENZA codice 2FA
        $response = $this->postJson('/api/auth/login', [
            'login'    => $this->user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'two_factor_required' => true,
                    'message' => 'Inserisci il codice 2FA',
                ])
                ->assertJsonMissing(['token']); // NON deve dare token definitivo
    }

    /** @test */
    public function user_can_cancel_two_factor_setup()
    {
        $this->actingAs($this->user, 'sanctum');

        // Prima abilita il setup (salva il secret senza confermare)
        $this->postJson('/api/auth/2fa/enable')->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_2fa_enabled' => false,
        ]);
        $this->assertNotNull($this->user->fresh()->totp_secret);

        // Annulla il setup
        $response = $this->postJson('/api/auth/2fa/cancel');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Setup 2FA annullato']);

        // Il secret deve essere rimosso, la 2FA rimane disabilitata
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'totp_secret' => null,
            'is_2fa_enabled' => false,
        ]);
    }

    /** @test */
    public function cancel_two_factor_setup_fails_if_already_confirmed()
    {
        $this->actingAs($this->user, 'sanctum');

        // Abilita e conferma la 2FA
        $enable = $this->postJson('/api/auth/2fa/enable');
        $secret = $enable->json('secret');

        $google2fa = app(Google2FA::class);
        $code = $google2fa->getCurrentOtp($secret);

        $this->postJson('/api/auth/2fa/confirm', ['code' => $code])->assertStatus(200);

        // Tentare di annullare una 2FA già attiva deve restituire 400
        $response = $this->postJson('/api/auth/2fa/cancel');

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Usa la disattivazione per rimuovere la 2FA attiva']);
    }

    /** @test */
    public function user_can_logout()
    {
        // Login
        $login = $this->postJson('/api/auth/login', [
            'login'    => $this->user->email,
            'password' => 'password123',
        ]);

        $login->assertStatus(200);
        $token = $login->json('token');

        // /me prima logout → 200
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/auth/me')
            ->assertStatus(200);

        // Logout
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/auth/logout')
            ->assertStatus(200)
            ->assertJson(['message' => 'Logout effettuato con successo']);

        // /me dopo logout → 401
        // $this->withHeaders(['Authorization' => 'Bearer ' . $token])
        //     ->getJson('/api/auth/me')
        //     ->assertStatus(401);
    }
}