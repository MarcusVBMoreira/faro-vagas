<?php

namespace App\Enums;

/**
 * De onde vêm as vagas. Cada tipo terá um conector próprio (app/Services/Jobs/Connectors).
 */
enum JobSourceType: string
{
    case GithubIssues = 'github_issues';
    case Adzuna = 'adzuna';
    case Jooble = 'jooble';
    case Remotive = 'remotive';
    case Api = 'api';
    case Manual = 'manual';
}
