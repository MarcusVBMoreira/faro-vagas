<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Grava no perfil o que veio da IA ou do formulário, cuidando das habilidades:
 * - habilidades da IA ficam com source = "ai" e são trocadas a cada currículo novo;
 * - habilidades adicionadas pelo usuário (source = "manual") nunca são apagadas pela IA.
 */
class ProfileUpdater
{
    public function __construct(private SkillCatalog $catalog)
    {
    }

    /**
     * @param  array<string, mixed>  $data  resultado de ProfileNormalizer::normalize()
     */
    public function applyFromAi(User $user, array $data): Profile
    {
        $profile = $user->profile()->firstOrCreate();

        $fields = collect($data)
            ->only(['headline', 'summary', 'desired_level', 'area', 'city', 'state', 'current_semester', 'graduation_year', 'english_level'])
            ->reject(fn ($value) => $value === null)
            ->all();

        if (! empty($data['desired_modalities'])) {
            $fields['desired_modalities'] = $data['desired_modalities'];
        }

        $profile->fill($fields)->save();

        $aiSkills = $this->catalog->resolve($data['skills'] ?? []);
        $current = $profile->skills()->get();

        $sync = [];
        foreach ($current as $skill) {
            if ($skill->pivot->source === 'manual') {
                $sync[$skill->id] = ['source' => 'manual', 'proficiency' => $skill->pivot->proficiency];
            }
        }
        foreach ($aiSkills as $skill) {
            $sync[$skill->id] ??= ['source' => 'ai', 'proficiency' => 1];
        }

        $profile->skills()->sync($sync);

        return $profile->refresh();
    }

    /**
     * Lista de habilidades editada pelo usuário no formulário ("PHP, Laravel, Git").
     * Mantém a origem das que já existiam; as novas entram como "manual".
     */
    public function syncSkillsFromForm(Profile $profile, string $commaSeparated): void
    {
        $names = array_filter(array_map('trim', preg_split('/[,;\n]+/u', $commaSeparated) ?: []));
        $resolved = $this->catalog->resolve(array_slice($names, 0, 60));

        /** @var Collection<int, Skill> $current */
        $current = $profile->skills()->get()->keyBy('id');

        $sync = [];
        foreach ($resolved as $skill) {
            $existing = $current->get($skill->id);
            $sync[$skill->id] = [
                'source' => $existing?->pivot->source ?? 'manual',
                'proficiency' => $existing?->pivot->proficiency ?? 1,
            ];
        }

        $profile->skills()->sync($sync);
    }
}
