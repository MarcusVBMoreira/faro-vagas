<?php

namespace App\Services\Jobs\Connectors;

use App\Contracts\JobSourceConnector;
use App\Models\JobSource;
use App\Services\Jobs\JobData;
use App\Support\Html;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Remotive (remotive.com): vagas remotas do mundo todo, por API pública e gratuita.
 * Os termos pedem crédito e link para a Remotive e poucas consultas por dia (rodamos 1x).
 *
 * Só ficam as vagas que aceitam pessoas no Brasil: candidate_required_location
 * "Worldwide", "Anywhere", "LATAM", "Americas", "South America" ou "Brazil".
 * Vagas "USA only", "Europe only" etc. são descartadas.
 */
class RemotiveConnector implements JobSourceConnector
{
    /** Categoria da Remotive → área do Faro (App\Support\Areas). */
    private const CATEGORY_AREAS = [
        'software' => 'tecnologia', 'devops' => 'tecnologia', 'sysadmin' => 'tecnologia', 'data' => 'tecnologia',
        'qa' => 'tecnologia', 'product' => 'tecnologia', 'customer' => 'atendimento', 'support' => 'atendimento',
        'design' => 'design', 'marketing' => 'comunicacao', 'writing' => 'comunicacao', 'sales' => 'comercial',
        'business' => 'comercial', 'finance' => 'contabilidade', 'legal' => 'direito', 'human resources' => 'rh',
        'project' => 'administracao', 'education' => 'educacao', 'teaching' => 'educacao', 'medical' => 'saude', 'health' => 'saude',
    ];

    private const ACCEPTED = ['worldwide', 'anywhere', 'latam', 'latin america', 'americas', 'south america', 'brazil', 'brasil'];

    public function isConfigured(): bool
    {
        return true; // API pública, sem chave
    }

    public function fetch(JobSource $source): iterable
    {
        $response = Http::timeout(60)->retry(2, 3000, throw: false)->acceptJson()
            ->get('https://remotive.com/api/remote-jobs', ['limit' => (int) (($source->config ?? [])['limit'] ?? 300)]);
        $response->throw();

        foreach ($response->json('jobs') ?? [] as $item) {
            if (empty($item['id']) || empty($item['url']) || empty($item['title'])) {
                continue;
            }
            if (! self::acceptsBrazil((string) ($item['candidate_required_location'] ?? ''))) {
                continue;
            }

            yield new JobData(
                externalId: (string) $item['id'],
                url: $item['url'],
                title: Html::toText($item['title'], 250),
                description: Html::toText($item['description'] ?? ''),
                company: $item['company_name'] ?? null,
                locationText: $item['candidate_required_location'] ?? null,
                remote: true,
                international: true,
                contractType: match ($item['job_type'] ?? null) {
                    'internship' => 'estagio',
                    'contract', 'freelance' => 'freelancer',
                    default => null,
                },
                category: self::area((string) ($item['category'] ?? '')) ?? ($item['category'] ?? null),
                publishedAt: filled($item['publication_date'] ?? null) ? CarbonImmutable::parse($item['publication_date']) : null,
                tags: array_values(array_filter((array) ($item['tags'] ?? []), 'is_string')),
            );
        }
    }

    /** Devolve o nome da área em português (o JobClassifier transforma em chave). */
    public static function area(string $category): ?string
    {
        $category = Str::lower($category);
        foreach (self::CATEGORY_AREAS as $word => $area) {
            if (str_contains($category, $word)) {
                return \App\Support\Areas::ALL[$area];
            }
        }

        return null;
    }

    public static function acceptsBrazil(string $location): bool
    {
        $location = Str::lower(Str::ascii($location));
        if ($location === '') {
            return false;
        }

        foreach (self::ACCEPTED as $word) {
            if (str_contains($location, $word)) {
                return true;
            }
        }

        return false;
    }
}
