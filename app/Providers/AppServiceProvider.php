<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Ability assegnata al token temporaneo emesso dopo la password quando la 2FA è attiva.
     */
    public const TWO_FACTOR_ABILITY = '2fa';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureTokenAuthentication();
        $this->configureRateLimiting();
        $this->configurePasswordReset();
    }

    /**
     * Il guard Sanctum accetta solo token completi di utenti attivi:
     * il token temporaneo della 2FA serve unicamente a /auth/2fa/verify,
     * che lo legge dal body, quindi non deve mai autenticare una rotta.
     */
    private function configureTokenAuthentication(): void
    {
        Sanctum::authenticateAccessTokensUsing(
            fn (PersonalAccessToken $token, bool $isValid) => $isValid
                && ! in_array(self::TWO_FACTOR_ABILITY, $token->abilities ?? [], true)
                && $token->tokenable?->is_active !== false
        );
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

        // Limite per coppia login+IP (brute force su un account) e per IP (credential stuffing)
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('login')) . '|' . $request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        // Ogni token temporaneo ha pochi tentativi: il codice TOTP ha solo 6 cifre
        RateLimiter::for('two-factor', fn (Request $request) => [
            Limit::perMinute(5)->by(hash('sha256', (string) $request->input('temp_token'))),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(3)->by(Str::lower((string) $request->input('email')) . '|' . $request->ip()),
            Limit::perMinute(10)->by($request->ip()),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
    }

    /**
     * Il link di reset punta alla pagina del pannello admin, non a una rotta web di Laravel.
     */
    private function configurePasswordReset(): void
    {
        $resetUrl = fn (User $user, string $token) => rtrim(config('app.frontend_url'), '/')
            . '/reimposta-password?' . http_build_query(['token' => $token, 'email' => $user->email]);

        ResetPassword::createUrlUsing($resetUrl);

        ResetPassword::toMailUsing(fn (User $user, string $token) => (new MailMessage)
            ->subject('Reimposta la password - ' . config('app.name'))
            ->greeting('Ciao,')
            ->line('Abbiamo ricevuto una richiesta di reimpostazione della password del tuo account.')
            ->action('Reimposta password', $resetUrl($user, $token))
            ->line('Il link scade tra ' . config('auth.passwords.users.expire') . ' minuti.')
            ->line('Se non hai richiesto tu il reset, ignora questa email: la password attuale resta valida.')
            ->salutation('Pokémon Collection'));
    }
}
