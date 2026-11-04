<?php

namespace App\Services\Jobs;

use Carbon\CarbonImmutable;

/**
 * Uma vaga como veio da fonte, já em formato comum a todos os conectores.
 * Campos que a fonte não informa ficam null e são preenchidos pelo JobClassifier ou pela IA.
 */
final class JobData
{
    public function __construct(
        public string $externalId,
        public string $url,
        public string $title,
        public string $description,
        public ?string $company = null,
        public ?string $locationText = null,
        public ?string $city = null,
        public ?string $state = null,
        public bool $remote = false,
        public bool $international = false,
        public ?string $contractType = null,
        public ?string $category = null,
        public ?int $salaryMin = null,
        public ?int $salaryMax = null,
        public ?CarbonImmutable $publishedAt = null,
        /** @var list<string> */
        public array $tags = [],
    ) {
    }
}
