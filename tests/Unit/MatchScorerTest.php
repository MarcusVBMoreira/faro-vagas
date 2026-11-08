<?php

namespace Tests\Unit;

use App\Enums\EducationLevel;
use App\Enums\JobLevel;
use App\Models\JobPosting;
use App\Models\Profile;
use App\Models\Skill;
use App\Services\Matching\MatchScorer;
use App\Services\Matching\Vector;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Tests\TestCase;

class MatchScorerTest extends TestCase
{
    private function skill(int $id, string $name, bool $required): Skill
    {
        $skill = new Skill(['name' => $name]);
        $skill->id = $id;
        $skill->setRelation('pivot', new Pivot(['is_required' => $required]));

        return $skill;
    }

    private function job(array $attributes, array $skills = []): JobPosting
    {
        $job = new JobPosting($attributes);
        $job->setRelation('skills', collect($skills));

        return $job;
    }

    private function profile(array $attributes = []): Profile
    {
        return new Profile([
            'area' => 'administracao',
            'desired_level' => JobLevel::Assistente,
            'desired_modalities' => ['presencial', 'hibrido'],
            'desired_contracts' => ['clt'],
            'education_level' => EducationLevel::Medio,
            ...$attributes,
        ]);
    }

    public function test_perfect_fit_scores_high_with_reasons(): void
    {
        $job = $this->job(
            ['area' => 'administracao', 'level' => 'assistente', 'modality' => 'presencial', 'contract_type' => 'clt', 'education_level' => 'medio'],
            [$this->skill(1, 'Excel', true), $this->skill(2, 'Atendimento ao cliente', true), $this->skill(3, 'SAP', false)],
        );

        $r = (new MatchScorer)->score($this->profile(), [1 => true, 2 => true], $job, 0.80);

        $this->assertGreaterThanOrEqual(90, $r['score']);
        $this->assertContains(['k' => 'skill', 'v' => 'Excel'], $r['strengths']);
        $this->assertContains(['k' => 'area'], $r['strengths']);
        $this->assertContains(['k' => 'level'], $r['strengths']);
        $this->assertContains(['k' => 'nice', 'v' => 'SAP'], $r['gaps']);
    }

    public function test_missing_required_skills_and_other_field_score_low(): void
    {
        $job = $this->job(
            ['area' => 'tecnologia', 'level' => 'senior', 'modality' => 'remoto', 'contract_type' => 'pj'],
            [$this->skill(10, 'Kubernetes', true), $this->skill(11, 'Go', true)],
        );

        $r = (new MatchScorer)->score($this->profile(), [1 => true], $job, 0.30);

        $this->assertLessThan(20, $r['score']);
        $this->assertContains(['k' => 'skill', 'v' => 'Kubernetes'], $r['gaps']);
        $this->assertContains(['k' => 'level', 'v' => 'senior'], $r['gaps']);
        $this->assertContains(['k' => 'modality', 'v' => 'remoto'], $r['gaps']);
        $this->assertContains(['k' => 'contract', 'v' => 'pj'], $r['gaps']);
    }

    public function test_sparse_jobs_get_partial_credit_but_do_not_reach_the_top(): void
    {
        // Vaga só com área: 15 pela área + 40% dos outros 85 pontos
        $job = $this->job(['area' => 'administracao']);

        $this->assertSame(49, (new MatchScorer)->score($this->profile(), [], $job, null)['score']);
        $this->assertSame(40, (new MatchScorer)->score(new Profile, [], $this->job([]), null)['score']);
    }

    public function test_education_below_requirement_costs_points(): void
    {
        $base = ['area' => 'saude', 'education_level' => 'tecnico'];
        $scorer = new MatchScorer;

        $ok = $scorer->score($this->profile(['area' => 'saude', 'education_level' => EducationLevel::Tecnico]), [], $this->job($base), null);
        $low = $scorer->score($this->profile(['area' => 'saude', 'education_level' => EducationLevel::Medio]), [], $this->job($base), null);

        $this->assertSame(10, $ok['score'] - $low['score']);
        $this->assertContains(['k' => 'education', 'v' => 'tecnico'], $low['gaps']);
    }

    public function test_disability_information_never_changes_the_score(): void
    {
        $job = $this->job(['area' => 'administracao', 'is_pcd' => true], [$this->skill(1, 'Excel', true)]);
        $scorer = new MatchScorer;

        $this->assertSame(
            $scorer->score($this->profile(['is_pcd' => false]), [1 => true], $job, 0.6)['score'],
            $scorer->score($this->profile(['is_pcd' => true]), [1 => true], $job, 0.6)['score'],
        );
    }

    public function test_cosine_similarity(): void
    {
        $this->assertEqualsWithDelta(1.0, Vector::cosine([1, 2, 3], [2, 4, 6]), 1e-9);
        $this->assertEqualsWithDelta(0.0, Vector::cosine([1, 0], [0, 1]), 1e-9);
        $this->assertNull(Vector::cosine([1, 2], [1, 2, 3]));
        $this->assertNull(Vector::cosine(null, [1]));
        $this->assertNull(Vector::cosine([0, 0], [1, 1]));
    }

    public function test_requirements_are_compared_with_the_whole_profile_text(): void
    {
        $student = $this->profile(['area' => 'tecnologia', 'desired_level' => JobLevel::Estagio, 'education_level' => EducationLevel::SuperiorCursando]);
        $job = $this->job(['area' => 'tecnologia'], [
            $this->skill(20, 'conhecimento em Lógica de programação', true),   // "programação" e "lógica" estão no perfil
            $this->skill(21, 'Node.js', true),                                  // perfil tem "Node"
            $this->skill(22, 'ciencias_da_computacao', true),                   // formação: atende quem está cursando
            $this->skill(23, 'Go', true),                                       // "algo" no perfil não vale como "Go"
            $this->skill(24, 'PCD', true),                                      // ignorado
        ]);
        $evidence = \App\Support\Grounding::plain('Lógica de programação, Node, React. Gosto de aprender algo novo.');

        $r = (new MatchScorer)->score($student, [], $job, null, $evidence);

        $have = collect($r['strengths'])->where('k', 'skill')->pluck('v')->all();
        $this->assertEqualsCanonicalizing(['conhecimento em Lógica de programação', 'Node.js', 'ciencias_da_computacao'], $have);
        $this->assertSame([['k' => 'skill', 'v' => 'Go']], array_values(array_filter($r['gaps'], fn ($g) => $g['k'] === 'skill')));
        $this->assertEqualsWithDelta(0.75, $r['parts']['skills'], 0.001); // 3 de 4 (PcD fora da conta)
    }

    public function test_similarity_calibration(): void
    {
        $scorer = new MatchScorer;
        $job = $this->job([]);

        $this->assertSame(0.0, $scorer->score(new Profile, [], $job, 0.40)['parts']['similarity']);
        $this->assertEqualsWithDelta(0.6, $scorer->score(new Profile, [], $job, 0.60)['parts']['similarity'], 0.001);
        $this->assertSame(1.0, $scorer->score(new Profile, [], $job, 0.75)['parts']['similarity']);
    }
}
