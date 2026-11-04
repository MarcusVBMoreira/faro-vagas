<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

/**
 * Catálogo inicial de habilidades. A IA também cria novas conforme lê currículos e vagas.
 */
class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'linguagem' => ['PHP', 'JavaScript', 'TypeScript', 'Python', 'Java', 'C#', 'Go', 'Kotlin', 'SQL', 'HTML', 'CSS'],
            'framework' => ['Laravel', 'Symfony', 'React', 'Vue.js', 'Angular', 'Next.js', 'Node.js', 'Express', 'NestJS', 'Spring Boot', '.NET', 'Django', 'Flask', 'Tailwind CSS'],
            'banco' => ['MySQL', 'PostgreSQL', 'SQL Server', 'MongoDB', 'Redis', 'SQLite'],
            'ferramenta' => ['Git', 'GitHub', 'Docker', 'Linux', 'AWS', 'Azure', 'Google Cloud', 'CI/CD', 'REST APIs', 'Testes automatizados', 'Figma', 'Power BI', 'Excel'],
            'soft skill' => ['Comunicação', 'Trabalho em equipe', 'Inglês', 'Metodologias ágeis'],
        ];

        foreach ($skills as $category => $names) {
            foreach ($names as $name) {
                Skill::updateOrCreate(
                    ['slug' => Skill::slugFor($name)],
                    ['name' => $name, 'category' => $category],
                );
            }
        }
    }
}
