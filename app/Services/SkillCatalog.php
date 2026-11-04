<?php

namespace App\Services;

use App\Models\Skill;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Transforma nomes livres ("js", "React.js", "postgres") em habilidades do catálogo,
 * para que currículo e vaga falem a mesma língua na hora do match.
 */
class SkillCatalog
{
    /** Apelidos comuns → nome oficial. Chaves em minúsculas e sem acento. */
    private const ALIASES = [
        'js' => 'JavaScript',
        'javascript' => 'JavaScript',
        'ts' => 'TypeScript',
        'node' => 'Node.js',
        'nodejs' => 'Node.js',
        'node js' => 'Node.js',
        'react js' => 'React',
        'reactjs' => 'React',
        'react.js' => 'React',
        'vue' => 'Vue.js',
        'vuejs' => 'Vue.js',
        'next' => 'Next.js',
        'nextjs' => 'Next.js',
        'postgres' => 'PostgreSQL',
        'postgresql' => 'PostgreSQL',
        'mysql' => 'MySQL',
        'mongo' => 'MongoDB',
        'html5' => 'HTML',
        'css3' => 'CSS',
        'c sharp' => 'C#',
        'csharp' => 'C#',
        'dotnet' => '.NET',
        'golang' => 'Go',
        'tailwind' => 'Tailwind CSS',
        'tailwindcss' => 'Tailwind CSS',
        'spring' => 'Spring Boot',
        'api rest' => 'REST APIs',
        'rest' => 'REST APIs',
        'apis rest' => 'REST APIs',
        'ingles' => 'Inglês',
        'english' => 'Inglês',
        'scrum' => 'Metodologias ágeis',
        'agile' => 'Metodologias ágeis',
    ];

    /**
     * @param  iterable<string>  $names
     * @return Collection<int, Skill>
     */
    public function resolve(iterable $names): Collection
    {
        $skills = collect();

        foreach ($names as $name) {
            $name = $this->canonical($name);
            if ($name === '') {
                continue;
            }

            $slug = Skill::slugFor($name);
            if ($slug === '' || $skills->has($slug)) {
                continue;
            }

            $skills[$slug] = Skill::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        return $skills->values();
    }

    public function canonical(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));
        $key = Str::lower(Str::ascii($name));

        return self::ALIASES[$key] ?? $name;
    }
}
