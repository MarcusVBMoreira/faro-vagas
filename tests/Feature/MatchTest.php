<?php

namespace Tests\Feature;

use App\Jobs\EmbedPendingJobPostings;
use App\Models\JobMatch;
use App\Models\JobPosting;
use App\Models\Profile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    private function skill(string $name): Skill
    {
        return Skill::firstOrCreate(['slug' => \Illuminate\Support\Str::slug($name)], ['name' => $name, 'category' => 'escritorio']);
    }

    private function job(string $title, string $area, array $required, array $extra = []): JobPosting
    {
        $job = JobPosting::factory()->create([
            'title' => $title, 'area' => $area, 'level' => 'assistente', 'modality' => 'presencial', 'contract_type' => 'clt',
            'description' => $title.'. Requisitos: '.implode(', ', $required).'.', 'extracted_at' => now(), ...$extra,
        ]);
        $job->skills()->sync(collect($required)->mapWithKeys(fn ($n) => [$this->skill($n)->id => ['is_required' => true]])->all());

        return $job;
    }

    private function userWithProfile(array $profile = []): User
    {
        $user = User::factory()->create();
        $p = Profile::factory()->for($user)->create([
            'desired_role' => 'Assistente administrativo', 'area' => 'administracao', 'desired_level' => 'assistente',
            'desired_modalities' => ['presencial'], 'desired_contracts' => ['clt'], 'education_level' => 'medio', ...$profile,
        ]);
        $p->skills()->sync([$this->skill('Excel')->id => ['source' => 'manual'], $this->skill('Atendimento ao cliente')->id => ['source' => 'manual']]);

        return $user;
    }

    public function test_jobs_are_ranked_with_score_strengths_and_gaps(): void
    {
        $good = $this->job('Assistente Administrativo', 'administracao', ['Excel', 'Atendimento ao cliente']);
        $half = $this->job('Recepcionista', 'administracao', ['Excel', 'Inglês']);
        $bad = $this->job('Desenvolvedor Go Sênior', 'tecnologia', ['Go', 'Kubernetes'], ['level' => 'senior', 'modality' => 'remoto', 'contract_type' => 'pj']);
        (new EmbedPendingJobPostings)->handle(app(\App\Contracts\AiClient::class));
        $this->assertNotNull($good->fresh()->embedded_at);

        $user = $this->userWithProfile();
        $this->artisan('faro:compute-matches', ['--user' => $user->id, '--sync' => true])->assertSuccessful();

        $scores = JobMatch::where('user_id', $user->id)->pluck('score', 'job_posting_id');
        $this->assertGreaterThan($scores[$half->id], $scores[$good->id]);
        $this->assertGreaterThan($scores[$bad->id], $scores[$half->id]);
        $this->assertGreaterThanOrEqual(70, $scores[$good->id]); // 70 só com habilidades, área, nível e modelo; o resto vem da similaridade
        $this->assertNotNull($user->profile->fresh()->embedding);

        $this->actingAs($user)->get('/para-voce')
            ->assertOk()
            ->assertSeeInOrder(['Assistente Administrativo', 'Recepcionista', 'Desenvolvedor Go Sênior'])
            ->assertSee('Inglês')                       // falta na Recepcionista
            ->assertSee('Mesma área que você busca');
    }

    public function test_saving_the_profile_recalculates_matches(): void
    {
        $this->job('Vendedor de Loja', 'varejo', ['Vendas']);
        $user = $this->userWithProfile();

        $this->actingAs($user)->put('/perfil', ['desired_role' => 'Vendedor', 'area' => 'varejo', 'skills' => 'Vendas, Excel'])
            ->assertSessionHasNoErrors();

        $match = JobMatch::where('user_id', $user->id)->sole();
        $this->assertContains(['k' => 'skill', 'v' => 'Vendas'], $match->strengths);
        $this->assertNotNull($user->profile->fresh()->matches_computed_at);
    }

    public function test_feedback_bad_hides_the_job_and_good_is_kept(): void
    {
        $a = $this->job('Assistente Administrativo', 'administracao', ['Excel']);
        $b = $this->job('Auxiliar de Escritório', 'administracao', ['Excel']);
        $user = $this->userWithProfile();
        $this->artisan('faro:compute-matches', ['--user' => $user->id, '--sync' => true]);

        $matchA = JobMatch::where('job_posting_id', $a->id)->first();
        $matchB = JobMatch::where('job_posting_id', $b->id)->first();

        $this->actingAs($user)->post("/para-voce/{$matchA->id}/avaliar", ['feedback' => 'bad'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post("/para-voce/{$matchB->id}/avaliar", ['feedback' => 'good']);

        $this->actingAs($user)->get('/para-voce')->assertDontSee('Assistente Administrativo')->assertSee('Auxiliar de Escritório');

        // Recalcular não ressuscita a vaga escondida nem apaga a avaliação
        $this->artisan('faro:compute-matches', ['--user' => $user->id, '--sync' => true]);
        $this->assertNotNull($matchA->fresh()->dismissed_at);
        $this->assertSame('good', $matchB->fresh()->feedback);
    }

    public function test_cannot_rate_someone_elses_match(): void
    {
        $this->job('Assistente Administrativo', 'administracao', ['Excel']);
        $owner = $this->userWithProfile();
        $this->artisan('faro:compute-matches', ['--user' => $owner->id, '--sync' => true]);
        $match = JobMatch::firstOrFail();

        $this->actingAs($this->userWithProfile())->post("/para-voce/{$match->id}/avaliar", ['feedback' => 'bad'])->assertNotFound();
        $this->assertNull($match->fresh()->feedback);
    }

    public function test_international_and_closed_jobs_follow_the_rules(): void
    {
        $this->job('Remote Office Assistant', 'administracao', ['Excel'], ['is_international' => true]);
        $this->job('Assistente Encerrada', 'administracao', ['Excel'], ['status' => 'closed']);
        $user = $this->userWithProfile();

        $this->artisan('faro:compute-matches', ['--user' => $user->id, '--sync' => true]);
        $this->assertSame(0, JobMatch::count());

        $user->profile->update(['accepts_international' => true]);
        $this->artisan('faro:compute-matches', ['--user' => $user->id, '--sync' => true]);
        $this->assertSame(1, JobMatch::count());
    }

    public function test_empty_states(): void
    {
        $empty = User::factory()->create();
        $this->actingAs($empty)->get('/para-voce')->assertOk()->assertSee('Precisamos conhecer você primeiro.');

        $user = $this->userWithProfile(); // sem vagas no banco
        $this->actingAs($user)->get('/para-voce')->assertOk()->assertSee('Estamos calculando suas vagas.');
    }

    public function test_job_detail_shows_the_match(): void
    {
        $job = $this->job('Assistente Administrativo', 'administracao', ['Excel']);
        $user = $this->userWithProfile();
        $this->artisan('faro:compute-matches', ['--user' => $user->id, '--sync' => true]);

        $score = JobMatch::firstOrFail()->score;
        $this->actingAs($user)->get('/vagas/'.$job->id)->assertSee($score.'% compatível');
    }

    public function test_matches_work_without_ai(): void
    {
        config(['faro.ai.api_key' => null]); // IA indisponível
        $this->job('Assistente Administrativo', 'administracao', ['Excel']);
        $user = $this->userWithProfile();

        $this->artisan('faro:compute-matches', ['--user' => $user->id, '--sync' => true])->assertSuccessful();

        $this->assertNull(JobMatch::firstOrFail()->similarity);
        $this->assertNull($user->profile->fresh()->embedding);
    }
}
