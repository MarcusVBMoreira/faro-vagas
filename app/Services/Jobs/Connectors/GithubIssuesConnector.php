<?php

namespace App\Services\Jobs\Connectors;

use App\Contracts\JobSourceConnector;
use App\Models\JobSource;
use App\Services\Jobs\JobData;
use App\Support\Region;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/**
 * Repositórios de comunidades (ex.: backend-br/vagas) publicam cada vaga de tecnologia
 * como uma issue. A API oficial do GitHub permite ler essas issues.
 *
 * config da fonte: {"repo": "backend-br/vagas", "pages": 2}
 * Título típico: "[São Paulo] Desenvolvedor PHP Pleno na Acme" ou "[Remoto] ..."
 */
class GithubIssuesConnector implements JobSourceConnector
{
    public function isConfigured(): bool
    {
        return true; // API pública (60 req/h sem token, suficiente para 1 coleta por dia)
    }

    public function fetch(JobSource $source): iterable
    {
        $repo = ($source->config ?? [])['repo'] ?? null;
        if (! $repo) {
            return;
        }

        $http = Http::timeout(30)->acceptJson()->withHeaders(['User-Agent' => 'faro-vagas'])
            ->when(config('faro.jobs.github_token'), fn ($h, $token) => $h->withToken($token));

        for ($page = 1; $page <= (int) (($source->config ?? [])['pages'] ?? 2); $page++) {
            $issues = $http->get("https://api.github.com/repos/{$repo}/issues", [
                'state' => 'open', 'per_page' => 100, 'page' => $page, 'sort' => 'created', 'direction' => 'desc',
            ])->throw()->json();

            foreach ($issues as $issue) {
                if (isset($issue['pull_request']) || empty($issue['title'])) {
                    continue;
                }
                yield $this->map($issue);
            }

            if (count($issues) < 100) {
                break;
            }
        }
    }

    /** @param array<string, mixed> $issue */
    private function map(array $issue): JobData
    {
        $title = trim($issue['title']);
        $labels = array_map(fn ($l) => (string) ($l['name'] ?? ''), $issue['labels'] ?? []);

        // Local entre colchetes no começo do título
        $where = preg_match('/^\s*\[([^\]]+)\]/u', $title, $m) ? $m[1] : null;
        $place = Region::parse($where);
        $labelText = mb_strtolower(implode(' ', $labels));
        $company = preg_match('/\s(?:na|no|@|at)\s+([^\[\]\-|]{2,60})\s*$/iu', $title, $c) ? trim($c[1]) : null;

        return new JobData(
            externalId: (string) $issue['number'],
            url: $issue['html_url'],
            title: trim(preg_replace('/^\s*(\[[^\]]+\]\s*)+/u', '', $title)) ?: $title,
            description: mb_substr((string) ($issue['body'] ?? ''), 0, 20000),
            company: $company,
            locationText: $where,
            city: $place['city'],
            state: $place['state'],
            remote: $place['remote'] || str_contains($labelText, 'remoto'),
            contractType: match (true) {
                str_contains($labelText, 'estágio') || str_contains($labelText, 'estagio') => 'estagio',
                str_contains($labelText, 'pj') => 'pj',
                str_contains($labelText, 'clt') => 'clt',
                default => null,
            },
            category: 'Tecnologia',
            publishedAt: isset($issue['created_at']) ? CarbonImmutable::parse($issue['created_at']) : null,
            tags: $labels,
        );
    }
}
