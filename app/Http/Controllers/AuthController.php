<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use PragmaRX\Google2FALaravel\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Http\Requests\Auth\{LoginRequest, RegisterRequest, ForgotPasswordRequest, PasswordUpdateRequest};
use App\Mail\PasswordRecoveryEmail;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
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

        if (! $user || ! Hash::check($password, $user->password)) {
            return response()->json([
                'message' => 'Credenziali non valide',
            ], 422);
        }

        // Se 2FA non è abilitato, generiamo subito il token
        if (!$user->is_2fa_enabled) {
            // Genera token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user'  => $user,
                'token' => $token,
            ]);
        }

        // 2FA attivo → crea token temporaneo per la sfida
        $tempToken = $user->createToken(
            '2fa_challenge',
            ['2fa'], // abilities limitate
            now()->addMinutes(10) // scade in 10 minuti
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
        $user->is_2fa_enabled = true;
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

        $valid = $this->google2fa->verifyKey($user->totp_secret, $request->code);

        if (! $valid) {
            return response()->json(['message' => 'Codice non valido'], 422);
        }

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

        // Verifica il token temporaneo
        $token = PersonalAccessToken::findToken($request->temp_token);

        if (! $token) {
            return response()->json(['message' => 'Token temporaneo non valido o scaduto.'], 401);
        }

        $user = $token->tokenable;

        if (! $user || ! $user->is_2fa_enabled) {
            return response()->json(['message' => 'Operazione non valida.'], 400);
        }

        // Verifica codice TOTP
        $valid = $this->google2fa->verifyKey($user->totp_secret, $request->code);

        if (! $valid) {
            return response()->json(['message' => 'Codice 2FA non valido.'], 422);
        }

        // Tutto ok → revoca il temp token (opzionale ma pulito)
        $token->delete();

        // Crea token definitivo
        $authToken = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $authToken,
        ]);
    }

    public function disable2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        $valid = $this->google2fa->verifyKey($user->totp_secret, $request->code);

        if (! $valid) {
            return response()->json(['message' => 'Codice non valido'], 422);
        }

        $user->update([
            'is_2fa_enabled' => false,
            'totp_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return response()->json(['message' => '2FA disattivato']);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $password = Str::random(16);
        User::whereEmail($request->email)->update(['password' => Hash::make($password)]);
        Mail::to($request->email)->send(new PasswordRecoveryEmail($request->email, $password));
        return response()->json(['message' => 'Nuove credenziali inviate con successo'], 200);
        
    }

    public function updatePassword(PasswordUpdateRequest $request, User $user)
    {
        $user->update(['password' => Hash::make($request->password)]);
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
}
