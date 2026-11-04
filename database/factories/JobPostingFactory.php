<?php

namespace Database\Factories;

use App\Enums\JobLevel;
use App\Enums\WorkModality;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        $url = fake()->unique()->url();
        [$title, $area] = fake()->randomElement([
            ['Auxiliar Administrativo', 'administracao'], ['Vendedor(a) de Loja', 'varejo'],
            ['Técnico(a) de Enfermagem', 'saude'], ['Estágio em Desenvolvimento PHP', 'tecnologia'],
            ['Auxiliar de Logística', 'logistica'], ['Analista Financeiro Júnior', 'contabilidade'],
        ]);
        $company = fake()->company();

        return [
            'url' => $url,
            'title' => $title,
            'company' => $company,
            'description' => fake()->paragraphs(3, true),
            'level' => fake()->randomElement(JobLevel::cases()),
            'area' => $area,
            'modality' => fake()->randomElement(WorkModality::cases()),
            'city' => 'São Paulo',
            'state' => 'SP',
            'contract_type' => fake()->randomElement(['clt', 'estagio', 'pj']),
            'last_seen_at' => now(),
            'published_at' => now()->subDays(fake()->numberBetween(0, 20)),
            'status' => 'active',
            'content_hash' => JobPosting::makeHash($url, $title, $company),
        ];
    }
}
