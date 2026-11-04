<?php

namespace Database\Factories;

use App\Enums\JobLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Profile>
 */
class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'headline' => 'Assistente administrativo',
            'desired_role' => 'Assistente administrativo',
            'desired_level' => JobLevel::Assistente,
            'area' => fake()->randomElement(\App\Support\Areas::keys()),
            'city' => 'Santo André',
            'state' => 'SP',
            'desired_modalities' => ['hibrido', 'remoto'],
            'current_semester' => fake()->numberBetween(1, 8),
            'graduation_year' => 2029,
            'english_level' => 'intermediario',
            'languages' => [['language' => 'ingles', 'level' => 'intermediario']],
            'open_to_work' => true,
            'digest_enabled' => true,
        ];
    }
}
