<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Grava no perfil o que veio da IA ou do formulário.
 *
 * Regra geral: o que a pessoa escreveu (source = "manual") nunca é apagado pela IA.
 * A cada currículo novo, só os itens que vieram da IA (source = "ai") são trocados.
 */
class ProfileUpdater
{
    private const AI_FIELDS = [
        'headline', 'summary', 'desired_role', 'area', 'desired_level', 'city', 'state', 'phone',
        'linkedin_url', 'education_level', 'current_semester', 'graduation_year', 'english_level',
    ];

    private const AI_LIST_FIELDS = ['desired_modalities', 'desired_contracts', 'languages', 'driver_license'];

    public function __construct(private SkillCatalog $catalog)
    {
    }

    /**
     * @param  array<string, mixed>  $data  resultado de ProfileNormalizer::normalize()
     */
    public function applyFromAi(User $user, array $data): Profile
    {
        return DB::transaction(function () use ($user, $data) {
            $profile = $user->profile()->firstOrCreate();

            $fields = collect($data)->only(self::AI_FIELDS)->reject(fn ($value) => $value === null)->all();
            foreach (self::AI_LIST_FIELDS as $field) {
                if (! empty($data[$field])) {
                    $fields[$field] = $data[$field];
                }
            }
            $profile->fill($fields)->save();

            $this->replaceAiItems($profile, 'experiences', $data['experiences'] ?? []);
            $this->replaceAiItems($profile, 'educations', $data['educations'] ?? []);
            $this->syncAiSkills($profile, $data['skills'] ?? []);

            return $profile->refresh();
        });
    }

    /**
     * Lista de habilidades editada pelo usuário no formulário ("Excel, Vendas, Atendimento").
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

    /** @param  list<array<string, mixed>>  $items */
    private function replaceAiItems(Profile $profile, string $relation, array $items): void
    {
        if ($items === []) {
            return; // a IA não achou nada: mantém o que existe
        }

        $profile->{$relation}()->where('source', 'ai')->delete();

        foreach ($items as $item) {
            $profile->{$relation}()->create([...$item, 'source' => 'ai']);
        }
    }

    /** @param  list<string>  $names */
    private function syncAiSkills(Profile $profile, array $names): void
    {
        $aiSkills = $this->catalog->resolve($names);

        $sync = [];
        foreach ($profile->skills()->get() as $skill) {
            if ($skill->pivot->source === 'manual') {
                $sync[$skill->id] = ['source' => 'manual', 'proficiency' => $skill->pivot->proficiency];
            }
        }
        foreach ($aiSkills as $skill) {
            $sync[$skill->id] ??= ['source' => 'ai', 'proficiency' => 1];
        }

        $profile->skills()->sync($sync);
    }
}
