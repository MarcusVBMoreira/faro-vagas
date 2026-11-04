<?php

namespace App\Services\Jobs\Connectors;

use App\Contracts\JobSourceConnector;
use App\Models\JobSource;
use App\Services\Jobs\JobData;
use App\Support\Html;
use App\Support\Region;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/**
 * Adzuna (adzuna.com.br): agregador que reúne vagas de vários sites de emprego do Brasil.
 * API oficial com chave gratuita (developer.adzuna.com). Os termos pedem que cada vaga
 * exibida mostre "Adzuna" com link para o site deles (feito na tela de vagas).
 *
 * config da fonte: {"country": "br", "where": "São Paulo", "queries": ["", "remoto"], "pages": 4, "max_days_old": 7}
 */
class AdzunaConnector implements JobSourceConnector
{
    private const BASE = 'https://api.adzuna.com/v1/api/jobs';

    public function isConfigured(): bool
    {
        return filled(config('faro.jobs.adzuna.app_id')) && filled(config('faro.jobs.adzuna.app_key'));
    }

    public function fetch(JobSource $source): iterable
    {
        $config = $source->config ?? [];
        $country = $config['country'] ?? 'br';

        foreach ($config['queries'] ?? [''] as $query) {
            for ($page = 1; $page <= (int) ($config['pages'] ?? 3); $page++) {
                $response = Http::timeout(30)->retry(2, 2000, throw: false)->acceptJson()
                    ->get(self::BASE."/{$country}/search/{$page}", array_filter([
                        'app_id' => config('faro.jobs.adzuna.app_id'),
                        'app_key' => config('faro.jobs.adzuna.app_key'),
                        'results_per_page' => 50,
                        'what' => $query ?: null,
                        'where' => $config['where'] ?? null,
                        'max_days_old' => $config['max_days_old'] ?? 7,
                        'sort_by' => 'date',
                    ]));

                $response->throw();
                $results = $response->json('results') ?? [];

                foreach ($results as $item) {
                    if ($job = $this->map($item, $country)) {
                        yield $job;
                    }
                }

                if (count($results) < 50) {
                    break;
                }
                usleep(500_000); // educação com a API
            }
        }
    }

    /** @param array<string, mixed> $item */
    private function map(array $item, string $country): ?JobData
    {
        if (empty($item['id']) || empty($item['redirect_url']) || empty($item['title'])) {
            return null;
        }

        $title = Html::toText($item['title'], 250);
        $description = Html::toText($item['description'] ?? '');
        $locationText = $item['location']['display_name'] ?? null;
        $place = Region::parse($locationText);

        // "area" vem como ["Brasil", "São Paulo", "Santo André"]: estado e cidade mais específicos
        $area = $item['location']['area'] ?? [];
        if (count($area) >= 3) {
            $place = [...$place, ...array_filter(Region::parse($area[2].', '.$area[1]), fn ($v) => $v !== null && $v !== false)];
        }

        $remote = $place['remote'] || Region::mentionsRemote(mb_strtolower($title.' '.mb_substr($description, 0, 600)));

        return new JobData(
            externalId: (string) $item['id'],
            url: $item['redirect_url'],
            title: $title,
            description: $description,
            company: $item['company']['display_name'] ?? null,
            locationText: $locationText,
            city: $place['city'],
            state: $place['state'],
            remote: $remote,
            international: $country !== 'br',
            contractType: match ($item['contract_type'] ?? null) {
                'permanent' => 'clt',
                default => null,
            },
            category: $item['category']['label'] ?? null,
            salaryMin: isset($item['salary_min']) ? (int) $item['salary_min'] : null,
            salaryMax: isset($item['salary_max']) ? (int) $item['salary_max'] : null,
            publishedAt: isset($item['created']) ? CarbonImmutable::parse($item['created']) : null,
        );
    }
}
