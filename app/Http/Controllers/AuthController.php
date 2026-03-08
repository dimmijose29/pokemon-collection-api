<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FALaravel\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Http\Requests\Auth\{LoginRequest,RegisterRequest};
use Illuminate\Support\Facades\Auth;

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
        $credentials = $request->getCredentials();

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenziali non valide.',
            ], 422);
        }

        $user = Auth::user();

        // Aggiorna ultimo login
        $user->update(['last_login_at' => now()]);

        // Se 2FA attivo → richiedi codice
        if ($user->is_2fa_enabled) {
            // Opzionale: invalidiamo token temporanei o forniamo flag
            return response()->json([
                'message' => 'Inserisci il codice 2FA per continuare.',
                'two_factor_required' => true,
            ], 200);
        }

        // Genera token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
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
            config('app.name'),
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
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        $valid = $this->google2fa->verifyKey($user->totp_secret, $request->code);

        if (! $valid) {
            return response()->json(['message' => 'Codice 2FA non valido'], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function disable2FA(Request $request)
    {
        $user = $request->user();

        $user->update([
            'is_2fa_enabled' => false,
            'totp_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        return response()->json(['message' => '2FA disattivato']);
    }
}