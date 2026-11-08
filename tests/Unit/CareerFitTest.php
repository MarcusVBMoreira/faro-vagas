<?php

namespace Tests\Unit;

use App\Enums\EducationLevel;
use App\Enums\JobLevel;
use App\Models\JobPosting;
use App\Models\Profile;
use App\Services\Matching\CareerFit;
use App\Support\ProfileNormalizer;
use Tests\TestCase;

class CareerFitTest extends TestCase
{
    private function student(array $extra = []): Profile
    {
        return new Profile(['desired_level' => JobLevel::Estagio, 'education_level' => EducationLevel::SuperiorCursando, 'current_semester' => 2, ...$extra]);
    }

    public function test_student_sees_internship_trainee_and_junior_only(): void
    {
        $p = $this->student();

        $this->assertNull(CareerFit::rejects($p, new JobPosting(['title' => 'Estágio em TI', 'level' => 'estagio'])));
        $this->assertNull(CareerFit::rejects($p, new JobPosting(['title' => 'Dev Front-end', 'level' => 'junior'])));
        $this->assertSame('level', CareerFit::rejects($p, new JobPosting(['title' => 'Dev Front-end', 'level' => 'pleno'])));
        $this->assertSame('level', CareerFit::rejects($p, new JobPosting(['title' => 'Desenvolvedor Sênior'])));   // nível pelo título
        $this->assertSame('level', CareerFit::rejects($p, new JobPosting(['title' => 'Analista de Sistemas Sr'])));
        $this->assertNull(CareerFit::rejects($p, new JobPosting(['title' => 'Desenvolvedor Web'])));              // sem nível: entra
    }

    public function test_degree_required_only_blocks_non_internship_jobs(): void
    {
        $p = $this->student();

        $this->assertSame('education', CareerFit::rejects($p, new JobPosting(['title' => 'Dev', 'level' => 'junior', 'education_level' => 'superior'])));
        $this->assertNull(CareerFit::rejects($p, new JobPosting(['title' => 'Estágio Dev', 'level' => 'estagio', 'education_level' => 'superior'])));
        $this->assertNull(CareerFit::rejects($p, new JobPosting(['title' => 'Dev', 'contract_type' => 'estagio', 'education_level' => 'superior'])));
    }

    public function test_explicit_accepted_levels_win(): void
    {
        $p = $this->student(['accepted_levels' => ['junior', 'pleno']]);

        $this->assertSame('level', CareerFit::rejects($p, new JobPosting(['title' => 'Estágio', 'level' => 'estagio'])));
        $this->assertNull(CareerFit::rejects($p, new JobPosting(['title' => 'Dev', 'level' => 'pleno'])));
    }

    public function test_student_without_desired_level_defaults_to_entry_levels(): void
    {
        $p = new Profile(['education_level' => EducationLevel::SuperiorCursando]);

        $this->assertSame(['estagio', 'trainee', 'junior'], CareerFit::acceptedLevels($p));
        $this->assertNull(CareerFit::acceptedLevels(new Profile));
    }

    public function test_level_abbreviations_in_titles(): void
    {
        $this->assertSame(JobLevel::Junior, ProfileNormalizer::level('Desenvolvedor PHP Jr'));
        $this->assertSame(JobLevel::Senior, ProfileNormalizer::level('Analista Sr.'));
        $this->assertSame(JobLevel::Pleno, ProfileNormalizer::level('Dev Java PL'));
        $this->assertNotSame(JobLevel::Pleno, ProfileNormalizer::level('Analista PL/SQL'));
        $this->assertSame(JobLevel::Especialista, ProfileNormalizer::level('Tech Lead'));
    }
}
