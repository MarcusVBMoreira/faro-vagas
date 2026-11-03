<?php

namespace Database\Seeders;

use App\Enums\JobSourceType;
use App\Models\JobSource;
use Illuminate\Database\Seeder;

/**
 * Fontes de vagas. Repositórios de comunidades no GitHub publicam cada vaga como uma issue,
 * e a API oficial do GitHub permite ler essas issues sem violar termos de uso.
 */
class JobSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['name' => 'Back-end Brasil (GitHub)', 'slug' => 'backend-br', 'type' => JobSourceType::GithubIssues, 'config' => ['repo' => 'backend-br/vagas']],
            ['name' => 'Front-end Brasil (GitHub)', 'slug' => 'frontendbr', 'type' => JobSourceType::GithubIssues, 'config' => ['repo' => 'frontendbr/vagas']],
            ['name' => 'Adicionada pelo usuário', 'slug' => 'manual', 'type' => JobSourceType::Manual, 'config' => null],
        ];

        foreach ($sources as $source) {
            JobSource::updateOrCreate(['slug' => $source['slug']], $source);
        }
    }
}
