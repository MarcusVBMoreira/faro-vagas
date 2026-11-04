<?php

namespace Database\Seeders;

use App\Enums\JobSourceType;
use App\Models\JobSource;
use Illuminate\Database\Seeder;

/**
 * Fontes de vagas, todas por API oficial ou pública:
 * - Adzuna e Jooble: agregadores com edição brasileira (todas as áreas), precisam de chave grátis;
 * - Remotive: vagas remotas internacionais que aceitam pessoas no Brasil;
 * - GitHub: comunidades de tecnologia publicam cada vaga como uma issue.
 */
class JobSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['name' => 'Adzuna Brasil', 'slug' => 'adzuna-br', 'type' => JobSourceType::Adzuna,
                'config' => ['country' => 'br', 'where' => 'São Paulo', 'queries' => ['', 'remoto', 'home office'], 'pages' => 4, 'max_days_old' => 7]],
            ['name' => 'Jooble Brasil', 'slug' => 'jooble-br', 'type' => JobSourceType::Jooble,
                'config' => ['location' => 'São Paulo', 'keywords' => ['', 'home office'], 'pages' => 3]],
            ['name' => 'Remotive (remotas internacionais)', 'slug' => 'remotive', 'type' => JobSourceType::Remotive, 'config' => ['limit' => 300]],
            ['name' => 'Back-end Brasil (GitHub)', 'slug' => 'backend-br', 'type' => JobSourceType::GithubIssues, 'config' => ['repo' => 'backend-br/vagas']],
            ['name' => 'Front-end Brasil (GitHub)', 'slug' => 'frontendbr', 'type' => JobSourceType::GithubIssues, 'config' => ['repo' => 'frontendbr/vagas']],
            ['name' => 'Adicionada pelo usuário', 'slug' => 'manual', 'type' => JobSourceType::Manual, 'config' => null],
        ];

        foreach ($sources as $source) {
            JobSource::updateOrCreate(['slug' => $source['slug']], $source);
        }
    }
}
