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
 * Jooble (br.jooble.org): buscador que reúne vagas de vários sites. API oficial com chave
 * gratuita, pedida pelo formulário em jooble.org/api/about. A descrição vem resumida
 * e o link passa pelo Jooble antes de chegar ao anúncio original.
 *
 * config da fonte: {"location": "São Paulo", "keywords": ["", "home office"], "pages": 3}
 */
class JoobleConnector implements JobSourceConnector
{
    public function isConfigured(): bool
    {
        return filled(config('faro.jobs.jooble.api_key'));
    }

    public function fetch(JobSource $source): iterable
    {
        $config = $source->config ?? [];

        foreach ($config['keywords'] ?? [''] as $keywords) {
            for ($page = 1; $page <= (int) ($config['pages'] ?? 3); $page++) {
                $response = Http::timeout(30)->retry(2, 2000, throw: false)->acceptJson()
                    ->post('https://jooble.org/api/'.config('faro.jobs.jooble.api_key'), [
                        'keywords' => $keywords,
                        'location' => $config['location'] ?? 'São Paulo',
                        'page' => (string) $page,
                        'ResultOnPage' => '50',
                    ]);

                $response->throw();
                $jobs = $response->json('jobs') ?? [];

                foreach ($jobs as $item) {
                    if ($job = $this->map($item)) {
                        yield $job;
                    }
                }

                if (count($jobs) < 50) {
                    break;
                }
                usleep(500_000);
            }
        }
    }

    /** @param array<string, mixed> $item */
    private function map(array $item): ?JobData
    {
        if (empty($item['link']) || empty($item['title'])) {
            return null;
        }

        $title = Html::toText($item['title'], 250);
        $description = Html::toText($item['snippet'] ?? '');
        $place = Region::parse($item['location'] ?? null);
        $type = mb_strtolower((string) ($item['type'] ?? ''));

        return new JobData(
            externalId: (string) ($item['id'] ?? md5($item['link'])),
            url: $item['link'],
            title: $title,
            description: $description,
            company: filled($item['company'] ?? null) ? $item['company'] : null,
            locationText: $item['location'] ?? null,
            city: $place['city'],
            state: $place['state'],
            remote: $place['remote'] || Region::mentionsRemote(mb_strtolower($title.' '.$description)),
            contractType: match (true) {
                str_contains($type, 'estág') || str_contains($type, 'estag') || str_contains($type, 'intern') => 'estagio',
                str_contains($type, 'tempor') => 'temporario',
                str_contains($type, 'clt') || str_contains($type, 'efetiv') => 'clt',
                default => null,
            },
            publishedAt: filled($item['updated'] ?? null) ? CarbonImmutable::parse($item['updated']) : null,
        );
    }
}
