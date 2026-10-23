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
            'is_2fa_enabled' => false,
        ]);
        $this->assertSame($response->json('secret'), $this->user->fresh()->totp_secret);
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

        $this->postJson('/api/auth/2fa/confirm', ['code' => $code])->assertStatus(200);

        // Il codice usato per la conferma non è riutilizzabile: serve quello del time-step successivo
        $nextCode = $google2fa->oathTotp($secret, $google2fa->getTimestamp() + 1);

        $response = $this->postJson('/api/auth/2fa/disable', [
            'password' => 'password123',
            'code' => $nextCode,
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

        // /me dopo logout → 401 (il guard memorizza l'utente: va azzerato tra le richieste)
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }

    // Sicurezza

    /**
     * Attiva e conferma la 2FA per l'utente di test e restituisce il secret.
     */
    private function enableTwoFactor(): string
    {
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        $this->user->forceFill([
            'totp_secret' => $secret,
            'is_2fa_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $secret;
    }

    private function loginForTempToken(): string
    {
        return $this->postJson('/api/auth/login', [
            'login'    => $this->user->email,
            'password' => 'password123',
        ])->assertStatus(200)->json('temp_token');
    }

    /** @test */
    public function two_factor_temp_token_cannot_access_protected_routes()
    {
        $this->user->update(['is_admin' => true]);
        $this->enableTwoFactor();
        $tempToken = $this->loginForTempToken();

        $this->withToken($tempToken)->getJson('/api/auth/me')->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($tempToken)->getJson('/api/users')->assertStatus(401);
    }

    /** @test */
    public function user_can_complete_login_with_two_factor_code()
    {
        $secret = $this->enableTwoFactor();
        $tempToken = $this->loginForTempToken();

        $response = $this->postJson('/api/auth/2fa/verify', [
            'temp_token' => $tempToken,
            'code'       => app(Google2FA::class)->getCurrentOtp($secret),
        ]);

        $response->assertStatus(200)->assertJsonStructure(['user', 'token']);

        $this->withToken($response->json('token'))->getJson('/api/auth/me')->assertStatus(200);
        // Il token temporaneo è stato consumato
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => '2fa_challenge']);
    }

    /** @test */
    public function two_factor_code_cannot_be_reused()
    {
        $secret = $this->enableTwoFactor();
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $this->postJson('/api/auth/2fa/verify', ['temp_token' => $this->loginForTempToken(), 'code' => $code])
            ->assertStatus(200);

        $this->postJson('/api/auth/2fa/verify', ['temp_token' => $this->loginForTempToken(), 'code' => $code])
            ->assertStatus(422);
    }

    /** @test */
    public function verify_two_factor_rejects_full_and_expired_tokens()
    {
        $secret = $this->enableTwoFactor();
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        // Un token completo non può essere usato come token di sfida
        $fullToken = $this->user->createToken('auth_token')->plainTextToken;
        $this->postJson('/api/auth/2fa/verify', ['temp_token' => $fullToken, 'code' => $code])
            ->assertStatus(401);

        // Token di sfida scaduto
        $tempToken = $this->loginForTempToken();
        $this->travel(6)->minutes();
        $this->postJson('/api/auth/2fa/verify', ['temp_token' => $tempToken, 'code' => $code])
            ->assertStatus(401);
    }

    /** @test */
    public function two_factor_verify_is_rate_limited_per_temp_token()
    {
        $this->enableTwoFactor();
        $tempToken = $this->loginForTempToken();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/2fa/verify', ['temp_token' => $tempToken, 'code' => '000000'])
                ->assertStatus(422);
        }

        $this->postJson('/api/auth/2fa/verify', ['temp_token' => $tempToken, 'code' => '000000'])
            ->assertStatus(429);
    }

    /** @test */
    public function totp_secret_is_encrypted_at_rest()
    {
        $secret = $this->enableTwoFactor();

        $stored = \DB::table('users')->where('id', $this->user->id)->value('totp_secret');

        $this->assertNotSame($secret, $stored);
        $this->assertSame($secret, $this->user->fresh()->totp_secret);
    }

    /** @test */
    public function disabling_two_factor_requires_password()
    {
        $secret = $this->enableTwoFactor();
        $this->actingAs($this->user, 'sanctum');

        $this->postJson('/api/auth/2fa/disable', [
            'password' => 'wrong-password',
            'code'     => app(Google2FA::class)->getCurrentOtp($secret),
        ])->assertStatus(422)->assertJsonValidationErrors('password');

        $this->assertTrue($this->user->fresh()->is_2fa_enabled);
    }

    /** @test */
    public function login_gives_same_error_for_unknown_user_and_wrong_password()
    {
        $unknown = $this->postJson('/api/auth/login', ['login' => 'nessuno@example.com', 'password' => 'password123']);
        $wrong = $this->postJson('/api/auth/login', ['login' => 'test@example.com', 'password' => 'sbagliata']);

        $unknown->assertStatus(422);
        $wrong->assertStatus(422);
        $this->assertSame($unknown->json(), $wrong->json());
    }

    /** @test */
    public function login_is_rate_limited()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', ['login' => 'test@example.com', 'password' => 'sbagliata'])
                ->assertStatus(422);
        }

        $this->postJson('/api/auth/login', ['login' => 'test@example.com', 'password' => 'password123'])
            ->assertStatus(429);
    }

    /** @test */
    public function inactive_user_cannot_login_and_loses_existing_tokens()
    {
        $token = $this->user->createToken('auth_token')->plainTextToken;
        $this->user->update(['is_active' => false]);

        $this->postJson('/api/auth/login', ['login' => 'test@example.com', 'password' => 'password123'])
            ->assertStatus(403)
            ->assertJsonMissing(['token']);

        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }

    /** @test */
    public function tokens_expire()
    {
        $token = $this->user->createToken('auth_token')->plainTextToken;

        $this->travel(config('sanctum.expiration') + 1)->minutes();

        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }

    // Password

    /** @test */
    public function user_can_change_own_password_with_current_password()
    {
        $current = $this->user->createToken('auth_token')->plainTextToken;
        $other = $this->user->createToken('auth_token')->plainTextToken;

        $this->withToken($current)->putJson('/api/auth/password', [
            'current_password'      => 'password123',
            'password'              => 'nuovaPassword1',
            'password_confirmation' => 'nuovaPassword1',
        ])->assertStatus(200);

        $this->assertTrue(\Hash::check('nuovaPassword1', $this->user->fresh()->password));

        // La sessione corrente resta valida, le altre vengono revocate
        $this->app['auth']->forgetGuards();
        $this->withToken($current)->getJson('/api/auth/me')->assertStatus(200);
        $this->app['auth']->forgetGuards();
        $this->withToken($other)->getJson('/api/auth/me')->assertStatus(401);
    }

    /** @test */
    public function changing_password_requires_correct_current_password()
    {
        $this->actingAs($this->user, 'sanctum');

        $this->putJson('/api/auth/password', [
            'current_password'      => 'sbagliata',
            'password'              => 'nuovaPassword1',
            'password_confirmation' => 'nuovaPassword1',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $this->assertTrue(\Hash::check('password123', $this->user->fresh()->password));
    }

    /** @test */
    public function user_cannot_change_another_users_password()
    {
        $victim = User::factory()->create(['password' => bcrypt('victimPassword1')]);
        $this->actingAs($this->user, 'sanctum');

        // La vecchia rotta con l'id utente non esiste più
        $this->putJson("/api/auth/{$victim->id}/update-password", [
            'password'              => 'hacked12345',
            'password_confirmation' => 'hacked12345',
        ])->assertStatus(404);

        $this->assertTrue(\Hash::check('victimPassword1', $victim->fresh()->password));
    }

    /** @test */
    public function forgot_password_sends_reset_link_without_changing_password()
    {
        \Notification::fake();

        $this->postJson('/api/auth/forgot-password', ['email' => 'test@example.com'])->assertStatus(200);

        \Notification::assertSentTo($this->user, \Illuminate\Auth\Notifications\ResetPassword::class);
        $this->assertTrue(\Hash::check('password123', $this->user->fresh()->password));
    }

    /** @test */
    public function forgot_password_does_not_reveal_unknown_emails()
    {
        \Notification::fake();

        $known = $this->postJson('/api/auth/forgot-password', ['email' => 'test@example.com']);
        $unknown = $this->postJson('/api/auth/forgot-password', ['email' => 'nessuno@example.com']);

        $known->assertStatus(200);
        $unknown->assertStatus(200);
        $this->assertSame($known->json(), $unknown->json());
    }

    /** @test */
    public function user_can_reset_password_with_valid_token()
    {
        $oldToken = $this->user->createToken('auth_token')->plainTextToken;
        $resetToken = \Password::createToken($this->user);

        $this->postJson('/api/auth/reset-password', [
            'token'                 => $resetToken,
            'email'                 => 'test@example.com',
            'password'              => 'nuovaPassword1',
            'password_confirmation' => 'nuovaPassword1',
        ])->assertStatus(200);

        $this->assertTrue(\Hash::check('nuovaPassword1', $this->user->fresh()->password));
        // Le sessioni esistenti vengono revocate
        $this->withToken($oldToken)->getJson('/api/auth/me')->assertStatus(401);
    }

    /** @test */
    public function reset_password_rejects_invalid_token()
    {
        $this->postJson('/api/auth/reset-password', [
            'token'                 => 'token-inventato',
            'email'                 => 'test@example.com',
            'password'              => 'nuovaPassword1',
            'password_confirmation' => 'nuovaPassword1',
        ])->assertStatus(422);

        $this->assertTrue(\Hash::check('password123', $this->user->fresh()->password));
    }
}
