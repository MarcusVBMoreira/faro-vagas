<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Conta: segurança (2FA e senha) e exclusão (LGPD).
 */
class AccountController extends Controller
{
    public function updateSecurity(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->two_factor_email = $request->boolean('two_factor_email');
        if ($request->filled('password')) {
            $user->password = $request->input('password');
        }
        $user->save();

        return redirect()->to(route('profile.show').'#seguranca')->with('status', __('Configurações de segurança salvas.'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_email' => ['required', Rule::in([$request->user()->email])],
        ], [
            'confirm_email.in' => __('Digite exatamente o seu e-mail para confirmar.'),
        ]);

        $user = $request->user();

        Storage::disk('local')->deleteDirectory('resumes/'.$user->id);
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        Auth::guard('web')->logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('Sua conta e todos os seus dados foram excluídos.'));
    }
}
