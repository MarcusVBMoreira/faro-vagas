<?php

namespace App\Services\Matching;

use App\Models\JobPosting;
use App\Models\Profile;
use App\Support\Areas;
use Illuminate\Support\Str;

/**
 * Monta o texto que vira embedding. Perfil e vaga usam o mesmo "formato"
 * (cargo, área, nível, habilidades, descrição) para os vetores serem comparáveis.
 */
final class EmbeddingText
{
    public static function forProfile(Profile $profile): string
    {
        $profile->loadMissing(['skills', 'experiences', 'educations']);

        return self::clean(implode("\n", array_filter([
            'Cargo: '.($profile->desired_role ?: $profile->headline),
            $profile->area ? 'Área: '.(Areas::ALL[$profile->area] ?? $profile->area) : null,
            $profile->desired_level ? 'Nível: '.$profile->desired_level->value : null,
            $profile->skills->isNotEmpty() ? 'Habilidades: '.$profile->skills->pluck('name')->implode(', ') : null,
            $profile->experiences->isNotEmpty() ? 'Experiência: '.$profile->experiences->map(fn ($e) => trim($e->role.' '.$e->description))->implode('; ') : null,
            $profile->educations->isNotEmpty() ? 'Formação: '.$profile->educations->pluck('course')->implode(', ') : null,
            $profile->summary,
        ])), 2000);
    }

    public static function forJob(JobPosting $job): string
    {
        $job->loadMissing('skills');

        return self::clean(implode("\n", array_filter([
            'Cargo: '.$job->title,
            $job->area ? 'Área: '.(Areas::ALL[$job->area] ?? $job->area) : null,
            $job->level ? 'Nível: '.$job->level->value : null,
            $job->skills->isNotEmpty() ? 'Habilidades: '.$job->skills->pluck('name')->implode(', ') : null,
            $job->ai_summary,
            Str::limit($job->description, 1200, ''),
        ])), 2000);
    }

    private static function clean(string $text, int $max): string
    {
        return Str::limit(trim(preg_replace("/[ \t]+/u", ' ', $text)), $max, '');
    }
}
