<?php

namespace App\Enums;

/**
 * De onde vêm as vagas. Cada tipo terá um conector próprio (app/Sources).
 */
enum JobSourceType: string
{
    case GithubIssues = 'github_issues';
    case Api = 'api';
    case Manual = 'manual';
}
