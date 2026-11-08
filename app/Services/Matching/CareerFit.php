<?php

namespace App\Services\Matching;

use App\Enums\EducationLevel;
use App\Enums\JobLevel;
use App\Models\JobPosting;
use App\Models\Profile;
use Illuminate\Support\Str;

/**
 * Filtro de "momento de carreira", antes da nota: quem busca estágio ou júnior
 * não deve ver vaga pleno/sênior, e quem ainda está na faculdade não deve ver vaga
 * que exige o diploma (exceto estágio, que pede justamente estar cursando).
 */
final class CareerFit
{
    /** Níveis vizinhos que fazem sentido para quem escolheu um nível desejado. */
    private const NEIGHBORS = [
        'aprendiz' => ['aprendiz', 'estagio'],
        'estagio' => ['estagio', 'trainee', 'junior'],
        'trainee' => ['estagio', 'trainee', 'junior'],
        'operacional' => ['operacional', 'assistente'],
        'assistente' => ['operacional', 'assistente', 'junior'],
        'tecnico' => ['tecnico', 'junior', 'pleno'],
        'junior' => ['trainee', 'junior', 'assistente'],
        'pleno' => ['junior', 'pleno', 'senior'],
        'senior' => ['pleno', 'senior', 'especialista'],
        'especialista' => ['senior', 'especialista'],
        'supervisao' => ['supervisao', 'coordenacao'],
        'coordenacao' => ['supervisao', 'coordenacao', 'gerencia'],
        'gerencia' => ['coordenacao', 'gerencia', 'diretoria'],
        'diretoria' => ['gerencia', 'diretoria'],
    ];

    /** Níveis em que exigir "superior" quer dizer "cursando" (o próprio estágio). */
    private const STUDENT_LEVELS = ['aprendiz', 'estagio', 'trainee'];

    /** @return list<string>|null null = sem filtro de nível */
    public static function acceptedLevels(Profile $profile): ?array
    {
        if (! empty($profile->accepted_levels)) {
            return array_values($profile->accepted_levels);
        }

        $levels = $profile->desired_level ? self::NEIGHBORS[$profile->desired_level->value] : null;

        // Universitário sem nível escolhido: estágio, trainee e júnior
        if ($levels === null && self::isStudent($profile)) {
            $levels = self::NEIGHBORS['estagio'];
        }

        return $levels;
    }

    public static function isStudent(Profile $profile): bool
    {
        return $profile->education_level === EducationLevel::SuperiorCursando || filled($profile->current_semester);
    }

    /** null = a vaga serve; senão o motivo ("level" ou "education"). */
    public static function rejects(Profile $profile, JobPosting $job): ?string
    {
        $accepted = self::acceptedLevels($profile);
        $level = $job->level?->value ?? self::levelFromTitle($job->title);

        if ($accepted !== null && $level !== null && ! in_array($level, $accepted, true)) {
            return 'level';
        }

        $isStudentJob = in_array($level, self::STUDENT_LEVELS, true)
            || in_array($job->contract_type, ['estagio', 'aprendiz'], true);

        if (! $isStudentJob && $job->education_level && $profile->education_level
            && $profile->education_level->rank() < $job->education_level->rank()) {
            return 'education';
        }

        return null;
    }

    private static function levelFromTitle(string $title): ?string
    {
        return \App\Support\ProfileNormalizer::level(Str::limit($title, 120, ''))?->value;
    }
}
