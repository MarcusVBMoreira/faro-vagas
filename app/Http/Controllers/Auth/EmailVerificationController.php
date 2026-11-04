<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\EmailCodeService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Confirmação do cadastro com código de 6 dígitos enviado por e-mail.
 */
class EmailVerificationController extends Controller
{
    public function show(Request $request, EmailCodeService $codes): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('profile.show');
        }

        // Contas criadas antes da verificação existir (ou com código vencido) recebem um código ao chegar aqui.
        $mailFailed = false;
        if (! $codes->hasActiveCode($user, EmailCodeService::VERIFY_EMAIL)) {
            $mailFailed = ! $codes->send($user, EmailCodeService::VERIFY_EMAIL);
        }

        return view('auth.verify-email', ['email' => $user->email, 'mailFailed' => $mailFailed]);
    }

    public function verify(Request $request, EmailCodeService $codes): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('profile.show');
        }

        if (! $codes->verify($user, EmailCodeService::VERIFY_EMAIL, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => __('Código inválido ou expirado. Confira o e-mail ou peça um novo código.')]);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        return redirect()->route('profile.show')
            ->with('status', __('E-mail confirmado! Agora envie seu currículo para a IA montar seu perfil.'));
    }

    public function resend(Request $request, EmailCodeService $codes): RedirectResponse
    {
        $user = $request->user();
        $key = 'email-code:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            return back()->withErrors(['code' => __('Aguarde :seconds segundos para pedir outro código.', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        RateLimiter::hit($key, 60);
        if (! $codes->send($user, EmailCodeService::VERIFY_EMAIL)) {
            return back()->withErrors(['code' => __('Não conseguimos enviar o e-mail agora. Tente de novo em alguns minutos.')]);
        }

        return back()->with('status', __('Enviamos um novo código para :email.', ['email' => $user->email]));
    }
}
