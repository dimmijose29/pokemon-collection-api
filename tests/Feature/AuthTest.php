<?php

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Foundation\Testing\RefreshDatabase;


uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'username' => 'testuser',
        'password' => bcrypt('password123'),
    ]);
});

test('user can register', function () {
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
});

test('user can login with email or username', function () {
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
});

test('user can enable two factor', function () {
    $this->actingAs($this->user, 'sanctum');

    $response = $this->postJson('/api/auth/2fa/enable');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'message',
                 'secret',
                 'qr_code',
             ]);

    expect($response->json('secret'))->not->toBeNull();
    expect($response->json('qr_code'))->toStartWith('data:image/svg+xml;base64,');

    // enable salva il secret ma NON attiva ancora la 2FA (serve confirm)
    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'is_2fa_enabled' => false,
    ]);
    expect($this->user->fresh()->totp_secret)->toBe($response->json('secret'));
});

test('user can confirm two factor with valid code', function () {
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
});

test('user cannot confirm with invalid code', function () {
    $this->actingAs($this->user, 'sanctum');
    $this->postJson('/api/auth/2fa/enable');

    $response = $this->postJson('/api/auth/2fa/confirm', [
        'code' => '000000', // codice invalido
    ]);

    $response->assertStatus(422)
             ->assertJson([
                 'message' => 'Codice non valido',
             ]);
});

test('user can disable two factor', function () {
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
});

test('login requires two factor code when enabled', function () {
    // 1. Abilita e conferma 2FA
    $this->actingAs($this->user, 'sanctum');

    $enable = $this->postJson('/api/auth/2fa/enable');
    $secret = $enable->json('secret');

    $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
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
            ->assertJsonMissing(['token']);
    // NON deve dare token definitivo
});

test('user can cancel two factor setup', function () {
    $this->actingAs($this->user, 'sanctum');

    // Prima abilita il setup (salva il secret senza confermare)
    $this->postJson('/api/auth/2fa/enable')->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'is_2fa_enabled' => false,
    ]);
    expect($this->user->fresh()->totp_secret)->not->toBeNull();

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
});

test('cancel two factor setup fails if already confirmed', function () {
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
});

test('user can logout', function () {
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
});

// Sicurezza

// Helper: test() restituisce il test in esecuzione (con $user, actingAs, postJson...)

/**
 * Attiva e conferma la 2FA per l'utente di test e restituisce il secret.
 */
function enableTwoFactor(): string
{
    $secret = app(Google2FA::class)->generateSecretKey();

    test()->user->forceFill([
        'totp_secret' => $secret,
        'is_2fa_enabled' => true,
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $secret;
}

function loginForTempToken(): string
{
    return test()->postJson('/api/auth/login', [
        'login'    => test()->user->email,
        'password' => 'password123',
    ])->assertStatus(200)->json('temp_token');
}

test('two factor temp token cannot access protected routes', function () {
    $this->user->update(['is_admin' => true]);
    enableTwoFactor();
    $tempToken = loginForTempToken();

    $this->withToken($tempToken)->getJson('/api/auth/me')->assertStatus(401);
    $this->app['auth']->forgetGuards();
    $this->withToken($tempToken)->getJson('/api/users')->assertStatus(401);
});

test('user can complete login with two factor code', function () {
    $secret = enableTwoFactor();
    $tempToken = loginForTempToken();

    $response = $this->postJson('/api/auth/2fa/verify', [
        'temp_token' => $tempToken,
        'code'       => app(Google2FA::class)->getCurrentOtp($secret),
    ]);

    $response->assertStatus(200)->assertJsonStructure(['user', 'token']);

    $this->withToken($response->json('token'))->getJson('/api/auth/me')->assertStatus(200);

    // Il token temporaneo è stato consumato
    $this->assertDatabaseMissing('personal_access_tokens', ['name' => '2fa_challenge']);
});

test('two factor code cannot be reused', function () {
    $secret = enableTwoFactor();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->postJson('/api/auth/2fa/verify', ['temp_token' => loginForTempToken(), 'code' => $code])
        ->assertStatus(200);

    $this->postJson('/api/auth/2fa/verify', ['temp_token' => loginForTempToken(), 'code' => $code])
        ->assertStatus(422);
});

test('verify two factor rejects full and expired tokens', function () {
    $secret = enableTwoFactor();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    // Un token completo non può essere usato come token di sfida
    $fullToken = $this->user->createToken('auth_token')->plainTextToken;
    $this->postJson('/api/auth/2fa/verify', ['temp_token' => $fullToken, 'code' => $code])
        ->assertStatus(401);

    // Token di sfida scaduto
    $tempToken = loginForTempToken();
    $this->travel(6)->minutes();
    $this->postJson('/api/auth/2fa/verify', ['temp_token' => $tempToken, 'code' => $code])
        ->assertStatus(401);
});

test('two factor verify is rate limited per temp token', function () {
    enableTwoFactor();
    $tempToken = loginForTempToken();

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/auth/2fa/verify', ['temp_token' => $tempToken, 'code' => '000000'])
            ->assertStatus(422);
    }

    $this->postJson('/api/auth/2fa/verify', ['temp_token' => $tempToken, 'code' => '000000'])
        ->assertStatus(429);
});

test('totp secret is encrypted at rest', function () {
    $secret = enableTwoFactor();

    $stored = \DB::table('users')->where('id', $this->user->id)->value('totp_secret');

    $this->assertNotSame($secret, $stored);
    expect($this->user->fresh()->totp_secret)->toBe($secret);
});

test('disabling two factor requires password', function () {
    $secret = enableTwoFactor();
    $this->actingAs($this->user, 'sanctum');

    $this->postJson('/api/auth/2fa/disable', [
        'password' => 'wrong-password',
        'code'     => app(Google2FA::class)->getCurrentOtp($secret),
    ])->assertStatus(422)->assertJsonValidationErrors('password');

    expect($this->user->fresh()->is_2fa_enabled)->toBeTrue();
});

test('login gives same error for unknown user and wrong password', function () {
    $unknown = $this->postJson('/api/auth/login', ['login' => 'nessuno@example.com', 'password' => 'password123']);
    $wrong = $this->postJson('/api/auth/login', ['login' => 'test@example.com', 'password' => 'sbagliata']);

    $unknown->assertStatus(422);
    $wrong->assertStatus(422);
    expect($wrong->json())->toBe($unknown->json());
});

test('login is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/auth/login', ['login' => 'test@example.com', 'password' => 'sbagliata'])
            ->assertStatus(422);
    }

    $this->postJson('/api/auth/login', ['login' => 'test@example.com', 'password' => 'password123'])
        ->assertStatus(429);
});

test('inactive user cannot login and loses existing tokens', function () {
    $token = $this->user->createToken('auth_token')->plainTextToken;
    $this->user->update(['is_active' => false]);

    $this->postJson('/api/auth/login', ['login' => 'test@example.com', 'password' => 'password123'])
        ->assertStatus(403)
        ->assertJsonMissing(['token']);

    $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
});

test('tokens expire', function () {
    $token = $this->user->createToken('auth_token')->plainTextToken;

    $this->travel(config('sanctum.expiration') + 1)->minutes();

    $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
});

test('user can change own password with current password', function () {
    $current = $this->user->createToken('auth_token')->plainTextToken;
    $other = $this->user->createToken('auth_token')->plainTextToken;

    $this->withToken($current)->putJson('/api/auth/password', [
        'current_password'      => 'password123',
        'password'              => 'nuovaPassword1',
        'password_confirmation' => 'nuovaPassword1',
    ])->assertStatus(200);

    expect(\Hash::check('nuovaPassword1', $this->user->fresh()->password))->toBeTrue();

    // La sessione corrente resta valida, le altre vengono revocate
    $this->app['auth']->forgetGuards();
    $this->withToken($current)->getJson('/api/auth/me')->assertStatus(200);
    $this->app['auth']->forgetGuards();
    $this->withToken($other)->getJson('/api/auth/me')->assertStatus(401);
});

test('changing password requires correct current password', function () {
    $this->actingAs($this->user, 'sanctum');

    $this->putJson('/api/auth/password', [
        'current_password'      => 'sbagliata',
        'password'              => 'nuovaPassword1',
        'password_confirmation' => 'nuovaPassword1',
    ])->assertStatus(422)->assertJsonValidationErrors('current_password');

    expect(\Hash::check('password123', $this->user->fresh()->password))->toBeTrue();
});

test('user cannot change another users password', function () {
    $victim = User::factory()->create(['password' => bcrypt('victimPassword1')]);
    $this->actingAs($this->user, 'sanctum');

    // La vecchia rotta con l'id utente non esiste più
    $this->putJson("/api/auth/{$victim->id}/update-password", [
        'password'              => 'hacked12345',
        'password_confirmation' => 'hacked12345',
    ])->assertStatus(404);

    expect(\Hash::check('victimPassword1', $victim->fresh()->password))->toBeTrue();
});

test('forgot password sends reset link without changing password', function () {
    \Notification::fake();

    $this->postJson('/api/auth/forgot-password', ['email' => 'test@example.com'])->assertStatus(200);

    \Notification::assertSentTo($this->user, \Illuminate\Auth\Notifications\ResetPassword::class);
    expect(\Hash::check('password123', $this->user->fresh()->password))->toBeTrue();
});

test('forgot password does not reveal unknown emails', function () {
    \Notification::fake();

    $known = $this->postJson('/api/auth/forgot-password', ['email' => 'test@example.com']);
    $unknown = $this->postJson('/api/auth/forgot-password', ['email' => 'nessuno@example.com']);

    $known->assertStatus(200);
    $unknown->assertStatus(200);
    expect($unknown->json())->toBe($known->json());
});

test('user can reset password with valid token', function () {
    $oldToken = $this->user->createToken('auth_token')->plainTextToken;
    $resetToken = \Password::createToken($this->user);

    $this->postJson('/api/auth/reset-password', [
        'token'                 => $resetToken,
        'email'                 => 'test@example.com',
        'password'              => 'nuovaPassword1',
        'password_confirmation' => 'nuovaPassword1',
    ])->assertStatus(200);

    expect(\Hash::check('nuovaPassword1', $this->user->fresh()->password))->toBeTrue();

    // Le sessioni esistenti vengono revocate
    $this->withToken($oldToken)->getJson('/api/auth/me')->assertStatus(401);
});

test('reset password rejects invalid token', function () {
    $this->postJson('/api/auth/reset-password', [
        'token'                 => 'token-inventato',
        'email'                 => 'test@example.com',
        'password'              => 'nuovaPassword1',
        'password_confirmation' => 'nuovaPassword1',
    ])->assertStatus(422);

    expect(\Hash::check('password123', $this->user->fresh()->password))->toBeTrue();
});