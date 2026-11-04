<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\EmailCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Segunda etapa do login (opcional): depois da senha, um código enviado por e-mail.
 * Enquanto o código não é confirmado, a pessoa não está logada: só existe um
 * "login pendente" na sessão, que expira em 15 minutos.
 */
class TwoFactorController extends Controller
{
    public const SESSION_KEY = 'login.two_factor';

    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.two-factor', ['email' => $this->mask($user->email)]);
    }

    public function verify(Request $request, EmailCodeService $codes): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => __('A verificação expirou. Entre de novo.')]);
        }

        if (! $codes->verify($user, EmailCodeService::LOGIN, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => __('Código inválido ou expirado. Confira o e-mail ou peça um novo código.')]);
        }

        $remember = (bool) $request->session()->get(self::SESSION_KEY.'.remember');
        $request->session()->forget(self::SESSION_KEY);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('profile.show'));
    }

    public function resend(Request $request, EmailCodeService $codes): RedirectResponse
    {
        $user = $this->pendingUser($request);

        if (! $user) {
            return redirect()->route('login');
        }

        $key = 'login-code:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->withErrors(['code' => __('Aguarde :seconds segundos para pedir outro código.', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        RateLimiter::hit($key, 60);
        if (! $codes->send($user, EmailCodeService::LOGIN)) {
            return back()->withErrors(['code' => __('Não conseguimos enviar o e-mail agora. Tente de novo em alguns minutos.')]);
        }

        return back()->with('status', __('Enviamos um novo código.'));
    }

    /** Chamado pelo LoginController quando a senha está certa e a pessoa usa 2FA. */
    public static function start(Request $request, User $user, bool $remember, EmailCodeService $codes): RedirectResponse
    {
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'remember' => $remember,
            'expires_at' => now()->addMinutes(EmailCodeService::TTL_MINUTES)->timestamp,
        ]);

        if (! $codes->send($user, EmailCodeService::LOGIN)) {
            $request->session()->forget(self::SESSION_KEY);

            return back()->withInput($request->only('email'))->withErrors(['email' => __('Não conseguimos enviar o e-mail agora. Tente de novo em alguns minutos.')]);
        }

        return redirect()->route('two-factor.show');
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::SESSION_KEY);

        if (! $pending || $pending['expires_at'] < now()->timestamp) {
            $request->session()->forget(self::SESSION_KEY);

            return null;
        }

        return User::find($pending['user_id']);
    }

    private function mask(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2) + [1 => ''];

        return mb_substr($name, 0, 2).str_repeat('•', max(1, mb_strlen($name) - 2)).'@'.$domain;
    }
}
