<?php

namespace App\Http\Controllers;

use App\Enums\JobLevel;
use App\Enums\WorkModality;
use App\Services\ProfileUpdater;
use App\Support\Brazil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile()->firstOrCreate();

        return view('profile.show', [
            'user' => $user,
            'profile' => $profile->load('skills'),
            'resume' => $user->currentResume,
            'levels' => JobLevel::cases(),
            'modalities' => WorkModality::cases(),
            'englishLevels' => Brazil::ENGLISH_LEVELS,
            'states' => Brazil::STATES,
        ]);
    }

    public function update(Request $request, ProfileUpdater $updater): RedirectResponse
    {
        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:160'],
            'desired_level' => ['nullable', Rule::enum(JobLevel::class)],
            'area' => ['nullable', 'string', 'max:60'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', Rule::in(Brazil::STATES)],
            'desired_modalities' => ['nullable', 'array'],
            'desired_modalities.*' => [Rule::enum(WorkModality::class)],
            'current_semester' => ['nullable', 'integer', 'between:1,12'],
            'graduation_year' => ['nullable', 'integer', 'between:2000,2045'],
            'english_level' => ['nullable', Rule::in(array_keys(Brazil::ENGLISH_LEVELS))],
            'skills' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = $request->user()->profile()->firstOrCreate();

        $profile->fill([
            ...collect($data)->except('skills')->all(),
            'desired_modalities' => array_values($data['desired_modalities'] ?? []),
            'digest_enabled' => $request->boolean('digest_enabled'),
        ])->save();

        $updater->syncSkillsFromForm($profile, $data['skills'] ?? '');

        return redirect()->route('profile.show')->with('status', 'Perfil salvo.');
    }

    /** Exclui a conta e todos os dados (LGPD). */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_email' => ['required', Rule::in([$request->user()->email])],
        ], [
            'confirm_email.in' => 'Digite exatamente o seu e-mail para confirmar.',
        ]);

        $user = $request->user();

        Storage::disk('local')->deleteDirectory('resumes/'.$user->id);

        Auth::guard('web')->logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Sua conta e todos os seus dados foram excluídos.');
    }
}
