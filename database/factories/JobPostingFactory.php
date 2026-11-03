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
        $title = fake()->randomElement(['Estágio em Desenvolvimento PHP', 'Desenvolvedor(a) Front-end Júnior', 'Estágio em Dados']);
        $company = fake()->company();

        return [
            'url' => $url,
            'title' => $title,
            'company' => $company,
            'description' => fake()->paragraphs(3, true),
            'level' => fake()->randomElement(JobLevel::cases()),
            'modality' => fake()->randomElement(WorkModality::cases()),
            'city' => 'São Paulo',
            'state' => 'SP',
            'contract_type' => 'estagio',
            'published_at' => now()->subDays(fake()->numberBetween(0, 20)),
            'status' => 'active',
            'content_hash' => JobPosting::makeHash($url, $title, $company),
        ];
    }
}
