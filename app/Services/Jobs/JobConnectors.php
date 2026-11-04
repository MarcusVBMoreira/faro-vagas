<?php

namespace App\Services\Jobs;

use App\Contracts\JobSourceConnector;
use App\Enums\JobSourceType;
use App\Services\Jobs\Connectors\AdzunaConnector;
use App\Services\Jobs\Connectors\GithubIssuesConnector;
use App\Services\Jobs\Connectors\JoobleConnector;
use App\Services\Jobs\Connectors\RemotiveConnector;

/** Qual conector atende cada tipo de fonte. */
class JobConnectors
{
    private const MAP = [
        'adzuna' => AdzunaConnector::class,
        'jooble' => JoobleConnector::class,
        'remotive' => RemotiveConnector::class,
        'github_issues' => GithubIssuesConnector::class,
    ];

    public function for(JobSourceType $type): ?JobSourceConnector
    {
        $class = self::MAP[$type->value] ?? null;

        return $class ? app($class) : null;
    }
}
