<?php

namespace App\Http\Controllers;

use App\Enums\EducationLevel;
use App\Models\Education;
use App\Models\Experience;
use App\Support\Brazil;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Experiências e formações: adicionar e remover itens do perfil.
 */
class ProfileItemController extends Controller
{
    public function storeExperience(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'start_month' => ['nullable', 'date_format:Y-m'],
            'end_month' => ['nullable', 'date_format:Y-m', 'after_or_equal:start_month'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $current = $request->boolean('is_current');

        $this->profile($request)->experiences()->create([
            'role' => $data['role'],
            'company' => $data['company'] ?? null,
            'location' => $data['location'] ?? null,
            'start_date' => isset($data['start_month']) ? $data['start_month'].'-01' : null,
            'end_date' => ! $current && isset($data['end_month']) ? $data['end_month'].'-01' : null,
            'is_current' => $current,
            'description' => $data['description'] ?? null,
            'source' => 'manual',
        ]);

        return redirect()->to(route('profile.show').'#experiencias')->with('status', __('Experiência adicionada.'));
    }

    public function destroyExperience(Request $request, Experience $experience): RedirectResponse
    {
        abort_unless($experience->profile_id === $this->profile($request)->id, 404);
        $experience->delete();

        return redirect()->to(route('profile.show').'#experiencias')->with('status', __('Experiência removida.'));
    }

    public function storeEducation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course' => ['required', 'string', 'max:150'],
            'institution' => ['nullable', 'string', 'max:150'],
            'level' => ['nullable', Rule::enum(EducationLevel::class)],
            'status' => ['nullable', Rule::in(array_keys(Brazil::EDUCATION_STATUS))],
            'start_year' => ['nullable', 'integer', 'between:1950,2045'],
            'end_year' => ['nullable', 'integer', 'between:1950,2045', 'gte:start_year'],
        ]);

        $this->profile($request)->educations()->create([...$data, 'source' => 'manual']);

        return redirect()->to(route('profile.show').'#formacao')->with('status', __('Formação adicionada.'));
    }

    public function destroyEducation(Request $request, Education $education): RedirectResponse
    {
        abort_unless($education->profile_id === $this->profile($request)->id, 404);
        $education->delete();

        return redirect()->to(route('profile.show').'#formacao')->with('status', __('Formação removida.'));
    }

    private function profile(Request $request)
    {
        return $request->user()->profile()->firstOrCreate();
    }
}
