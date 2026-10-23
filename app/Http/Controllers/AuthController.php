<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use PragmaRX\Google2FALaravel\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Http\Requests\Auth\{LoginRequest, RegisterRequest, ForgotPasswordRequest, PasswordUpdateRequest, ResetPasswordRequest, TwoFactorDisableRequest};
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    /** Durata del token temporaneo per completare il login con 2FA. */
    private const TWO_FACTOR_TOKEN_MINUTES = 5;

    protected $google2fa;

    public function __construct(Google2FA $google2fa)
    {
        $this->google2fa = $google2fa;
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());

        return response()->json([
            'user'  => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $login = trim($request->input('login'));
        $password = $request->input('password');

        $user = User::where('email', $login)
                    ->orWhere('username', $login)
                    ->first();

        // Stessa risposta per utente inesistente e password errata: non rivela quali account esistono
        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Credenziali non valide',
            ], 422);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Account disattivato. Contatta un amministratore.',
            ], 403);
        }

        // Se 2FA non è abilitato, generiamo subito il token
        if (!$user->is_2fa_enabled) {
            return $this->issueToken($user);
        }

        // 2FA attivo → token temporaneo utilizzabile solo da /auth/2fa/verify
        $tempToken = $user->createToken(
            '2fa_challenge',
            [AppServiceProvider::TWO_FACTOR_ABILITY],
            now()->addMinutes(self::TWO_FACTOR_TOKEN_MINUTES)
        )->plainTextToken;

        return response()->json([
            'two_factor_required' => true,
            'message' => 'Inserisci il codice 2FA',
            'temp_token' => $tempToken,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logout effettuato con successo',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    // Genera secret e QR per attivare 2FA
    public function enable2FA(Request $request)
    {
        $user = $request->user();

        if ($user->is_2fa_enabled) {
            return response()->json(['message' => '2FA già attivo'], 400);
        }

        $secret = $this->google2fa->generateSecretKey();
        $user->totp_secret = $secret;
        $user->totp_last_used_timestamp = null;
        $user->save();

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name',),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(400),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($qrCodeUrl);

        return response()->json([
            'secret'   => $secret,
            'qr_code'  => 'data:image/svg+xml;base64,' . base64_encode($qrSvg),
            'message'  => 'Scansiona il QR con Google Authenticator',
        ]);
    }

    // Conferma codice 2FA dopo scansione
    public function confirm2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (! $user->totp_secret) {
            return response()->json(['message' => 'Avvia prima la configurazione della 2FA'], 400);
        }

        if (! $this->verifyTotp($user, $request->code)) {
            return response()->json(['message' => 'Codice non valido'], 422);
        }

        $user->is_2fa_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->save();

        return response()->json(['message' => '2FA confermato con successo']);
    }

    // Verifica 2FA durante login
    public function verify2FA(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
            'code'       => 'required|string|size:6',
        ]);

        $token = PersonalAccessToken::findToken($request->temp_token);

        // findToken non controlla né la scadenza né le abilities: va fatto qui
        if (! $token
            || $token->abilities !== [AppServiceProvider::TWO_FACTOR_ABILITY]
            || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json(['message' => 'Token temporaneo non valido o scaduto.'], 401);
        }

        $user = $token->tokenable;

        if (! $user || ! $user->is_2fa_enabled || ! $user->is_active) {
            $token->delete();
            return response()->json(['message' => 'Operazione non valida.'], 400);
        }

        if (! $this->verifyTotp($user, $request->code)) {
            return response()->json(['message' => 'Codice 2FA non valido.'], 422);
        }

        $token->delete();

        return $this->issueToken($user);
    }

    public function disable2FA(TwoFactorDisableRequest $request)
    {
        $user = $request->user();

        if (! $user->is_2fa_enabled) {
            return response()->json(['message' => '2FA non attiva'], 400);
        }

        if (! $this->verifyTotp($user, $request->code)) {
            return response()->json(['message' => 'Codice non valido'], 422);
        }

        $user->update([
            'is_2fa_enabled' => false,
            'totp_secret' => null,
            'totp_last_used_timestamp' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return response()->json(['message' => '2FA disattivato']);
    }

    public function cancelSetup2FA(Request $request)
    {
        $user = $request->user();

        // Se la 2FA è già confermata, non si può annullare da qui
        if ($user->is_2fa_enabled) {
            return response()->json(['message' => 'Usa la disattivazione per rimuovere la 2FA attiva'], 400);
        }

        $user->totp_secret = null;
        $user->save();

        return response()->json(['message' => 'Setup 2FA annullato']);
    }

    // Invia il link di reset. Risposta identica che l'email esista o no.
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => "Se l'indirizzo è registrato, riceverai un'email con il link per reimpostare la password.",
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                // Chi aveva rubato una sessione la perde
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Link di reset non valido o scaduto.'], 422);
        }

        return response()->json(['message' => 'Password reimpostata. Ora puoi accedere.'], 200);
    }

    // Cambia la password dell'utente autenticato (mai di un altro utente)
    public function updatePassword(PasswordUpdateRequest $request)
    {
        $user = $request->user();
        $user->update(['password' => $request->password]);

        // Revoca tutte le altre sessioni, tiene quella corrente
        $user->tokens()->whereKeyNot($user->currentAccessToken()->getKey())->delete();

        return response()->json(['message' => 'Password aggiornata con successo'], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'username' => [
                'required', 'string', 'min:3', 'max:30',
                \Illuminate\Validation\Rule::unique('users', 'username')->ignore($user->id),
            ],
        ]);

        $user->update(['username' => $request->username]);

        return response()->json($user);
    }

    private function issueToken(User $user)
    {
        return response()->json([
            'user'  => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    /**
     * Verifica il codice TOTP rifiutando un codice già usato
     * (verifyKeyNewer accetta solo time-step successivi all'ultimo valido).
     */
    private function verifyTotp(User $user, string $code): bool
    {
        if (! $user->totp_secret) {
            return false;
        }

        // Con un old timestamp intero (anche 0) la libreria restituisce il time-step valido
        $timestamp = $this->google2fa->verifyKeyNewer($user->totp_secret, $code, $user->totp_last_used_timestamp ?? 0);

        if ($timestamp === false) {
            return false;
        }

        $user->forceFill(['totp_last_used_timestamp' => $timestamp])->save();

        return true;
    }
}
