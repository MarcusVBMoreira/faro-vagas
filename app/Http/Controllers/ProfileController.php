<?php

namespace App\Http\Controllers;

use App\Enums\ContractType;
use App\Enums\EducationLevel;
use App\Enums\JobLevel;
use App\Enums\WorkModality;
use App\Services\ProfileUpdater;
use App\Support\Areas;
use App\Support\Brazil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $profile = $user->profile()->firstOrCreate()->load(['skills', 'experiences', 'educations']);

        return view('profile.show', [
            'user' => $user,
            'profile' => $profile,
            'resume' => $user->currentResume,
            'completeness' => $profile->completeness(),
            'areas' => Areas::options(),
            'levels' => JobLevel::cases(),
            'modalities' => WorkModality::cases(),
            'contracts' => ContractType::cases(),
            'educationLevels' => EducationLevel::cases(),
            'languageOptions' => Brazil::translated(Brazil::LANGUAGES),
            'languageLevels' => Brazil::translated(Brazil::LANGUAGE_LEVELS),
            'availability' => Brazil::translated(Brazil::AVAILABILITY),
            'educationStatus' => Brazil::translated(Brazil::EDUCATION_STATUS),
            'states' => Brazil::STATES,
            'licenses' => Brazil::DRIVER_LICENSES,
        ]);
    }

    public function update(Request $request, ProfileUpdater $updater): RedirectResponse
    {
        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'desired_role' => ['nullable', 'string', 'max:120'],
            'area' => ['nullable', Rule::in(Areas::keys())],
            'desired_level' => ['nullable', Rule::enum(JobLevel::class)],
            'desired_modalities' => ['nullable', 'array'],
            'desired_modalities.*' => [Rule::enum(WorkModality::class)],
            'desired_contracts' => ['nullable', 'array'],
            'desired_contracts.*' => [Rule::enum(ContractType::class)],
            'salary_expectation' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'availability' => ['nullable', Rule::in(array_keys(Brazil::AVAILABILITY))],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', Rule::in(Brazil::STATES)],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\d\s()+\-]{10,20}$/'],
            'linkedin_url' => ['nullable', 'url:https', 'max:255', 'regex:/linkedin\.com\//i'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:255'],
            'driver_license' => ['nullable', 'array'],
            'driver_license.*' => [Rule::in(Brazil::DRIVER_LICENSES)],
            'education_level' => ['nullable', Rule::enum(EducationLevel::class)],
            'current_semester' => ['nullable', 'integer', 'between:1,12'],
            'graduation_year' => ['nullable', 'integer', 'between:1960,2045'],
            'languages' => ['nullable', 'array', 'max:8'],
            'languages.*.language' => ['nullable', Rule::in(array_keys(Brazil::LANGUAGES))],
            'languages.*.level' => ['nullable', Rule::in(array_keys(Brazil::LANGUAGE_LEVELS))],
            'skills' => ['nullable', 'string', 'max:2000'],
            'pcd' => ['nullable', Rule::in(['', 'sim', 'nao'])],
        ]);

        $profile = $request->user()->profile()->firstOrCreate();

        $languages = collect($data['languages'] ?? [])
            ->filter(fn ($row) => filled($row['language'] ?? null))
            ->unique('language')
            ->map(fn ($row) => ['language' => $row['language'], 'level' => $row['level'] ?? null])
            ->values()->all();

        $profile->fill([
            ...collect($data)->except(['skills', 'languages', 'pcd'])->all(),
            'desired_modalities' => array_values($data['desired_modalities'] ?? []),
            'desired_contracts' => array_values($data['desired_contracts'] ?? []),
            'driver_license' => array_values($data['driver_license'] ?? []),
            'languages' => $languages,
            'english_level' => collect($languages)->firstWhere('language', 'ingles')['level'] ?? null,
            'open_to_work' => $request->boolean('open_to_work'),
            'willing_to_relocate' => $request->boolean('willing_to_relocate'),
            'willing_to_travel' => $request->boolean('willing_to_travel'),
            'digest_enabled' => $request->boolean('digest_enabled'),
        ]);

        // PcD: dado sensível. Só é gravado com escolha explícita da pessoa (consentimento).
        $pcd = $data['pcd'] ?? '';
        $profile->is_pcd = $pcd === '' ? null : $pcd === 'sim';
        $profile->pcd_consent_at = $pcd === '' ? null : ($profile->pcd_consent_at ?? now());

        $profile->save();

        $updater->syncSkillsFromForm($profile, $data['skills'] ?? '');

        return redirect()->route('profile.show')->with('status', __('Perfil salvo.'));
    }
}
