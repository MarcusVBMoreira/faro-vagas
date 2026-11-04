<?php

namespace Tests\Feature;

use App\Jobs\StructureJobPosting;
use App\Models\JobPosting;
use App\Models\Profile;
use App\Models\User;
use Database\Seeders\JobSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JobPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_jobs_page_lists_and_filters(): void
    {
        $user = User::factory()->create();
        JobPosting::factory()->create(['title' => 'Técnico de Enfermagem', 'area' => 'saude', 'contract_type' => 'clt']);
        JobPosting::factory()->create(['title' => 'Vendedor Externo', 'area' => 'comercial', 'company' => 'Loja Azul', 'is_pcd' => true]);
        JobPosting::factory()->create(['title' => 'Vaga Encerrada', 'status' => 'closed']);
        JobPosting::factory()->create(['title' => 'Remote Support Agent', 'is_international' => true]);

        $this->actingAs($user)->get('/vagas')
            ->assertOk()
            ->assertSee('Técnico de Enfermagem')
            ->assertSee('Vendedor Externo')
            ->assertDontSee('Vaga Encerrada')
            ->assertDontSee('Remote Support Agent'); // internacionais só para quem aceita

        $this->actingAs($user)->get('/vagas?area=saude')->assertSee('Técnico de Enfermagem')->assertDontSee('Vendedor Externo');
        $this->actingAs($user)->get('/vagas?q=loja+azul')->assertSee('Vendedor Externo')->assertDontSee('Técnico de Enfermagem');
        $this->actingAs($user)->get('/vagas?pcd=1')->assertSee('Vendedor Externo')->assertDontSee('Técnico de Enfermagem');
        $this->actingAs($user)->get('/vagas?internacional=so')->assertSee('Remote Support Agent')->assertDontSee('Vendedor Externo');
        $this->actingAs($user)->get('/vagas?area=invalida')->assertSessionHasErrors('area');
    }

    public function test_international_jobs_show_for_who_accepts_them(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['accepts_international' => true]);
        JobPosting::factory()->create(['title' => 'Remote Support Agent', 'is_international' => true]);

        $this->actingAs($user)->get('/vagas')->assertSee('Remote Support Agent')->assertSee('Internacional · inglês');
    }

    public function test_profile_saves_international_preference(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        $this->actingAs($user)->put('/perfil', ['accepts_international' => '1'])->assertSessionHasNoErrors();

        $this->assertTrue($user->profile->fresh()->accepts_international);
    }

    public function test_job_detail_page_with_source_credit(): void
    {
        $this->seed(JobSourceSeeder::class);
        $user = User::factory()->create();
        $job = JobPosting::factory()->create([
            'title' => 'Auxiliar de Logística',
            'job_source_id' => \App\Models\JobSource::where('slug', 'adzuna-br')->value('id'),
            'url' => 'https://www.adzuna.com.br/land/ad/1',
        ]);

        $this->actingAs($user)->get('/vagas/'.$job->id)
            ->assertOk()
            ->assertSee('Auxiliar de Logística')
            ->assertSee('https://www.adzuna.com.br/land/ad/1', false)
            ->assertSee('href="https://www.adzuna.com.br"', false)
            ->assertSee('A IA ainda vai ler esta vaga');
    }

    public function test_user_can_add_a_private_job_by_hand(): void
    {
        Queue::fake();
        $this->seed(JobSourceSeeder::class);
        $ana = User::factory()->create();
        $bia = User::factory()->create();

        $this->actingAs($ana)->post('/vagas', [
            'title' => 'Estágio em Recursos Humanos',
            'company' => 'Empresa X',
            'city' => 'Mauá',
            'description' => 'Apoio em recrutamento e seleção, rotinas de departamento pessoal. Cursando Administração ou Psicologia.',
        ])->assertSessionHasNoErrors();

        $job = JobPosting::sole();
        $this->assertSame($ana->id, $job->user_id);
        $this->assertSame('SP', $job->state);
        $this->assertSame('estagio', $job->contract_type);
        $this->assertSame('rh', $job->area);
        Queue::assertPushedOn('jobs', StructureJobPosting::class);

        $this->actingAs($ana)->get('/vagas')->assertSee('Estágio em Recursos Humanos')->assertSee('Adicionada por você');
        $this->actingAs($bia)->get('/vagas')->assertDontSee('Estágio em Recursos Humanos');
        $this->actingAs($bia)->get('/vagas/'.$job->id)->assertNotFound();
        $this->actingAs($bia)->delete('/vagas/'.$job->id)->assertNotFound();

        $this->actingAs($ana)->delete('/vagas/'.$job->id)->assertRedirect(route('jobs.index'));
        $this->assertSame(0, JobPosting::count());
    }

    public function test_collected_jobs_cannot_be_deleted_by_users(): void
    {
        $job = JobPosting::factory()->create();

        $this->actingAs(User::factory()->create())->delete('/vagas/'.$job->id)->assertNotFound();
        $this->assertModelExists($job);
    }

    public function test_ai_completes_the_job_without_overwriting_known_fields(): void
    {
        Http::fake(['ia.test/api/generate' => Http::response(['response' => json_encode([
            'area' => 'Logística',
            'nivel' => 'Operacional',
            'escolaridade_minima' => 'Ensino médio completo',
            'habilidades_obrigatorias' => ['Empilhadeira', 'NR-11', 'Inventado pela IA'],
            'habilidades_desejaveis' => ['Excel'],
            'resumo' => 'Operar empilhadeira e conferir cargas no estoque.',
        ])])]);

        $job = JobPosting::factory()->create([
            'title' => 'Operador de Empilhadeira',
            'description' => 'Operar empilhadeira, conferir cargas no estoque. Curso de NR-11. Ensino médio completo. Desejável Excel.',
            'area' => null, 'level' => 'junior', 'education_level' => null,
        ]);

        StructureJobPosting::dispatchSync($job);

        $job->refresh()->load('skills');
        $this->assertSame('logistica', $job->area);
        $this->assertSame('junior', $job->level->value);            // já existia: não muda
        $this->assertSame('medio', $job->education_level->value);
        $this->assertNotNull($job->extracted_at);
        $this->assertSame('Operar empilhadeira e conferir cargas no estoque.', $job->ai_summary);
        $this->assertEqualsCanonicalizing(['Empilhadeira', 'NR-11'], $job->skills->where('pivot.is_required', true)->pluck('name')->all());
        $this->assertSame(['Excel'], $job->skills->where('pivot.is_required', false)->pluck('name')->values()->all());
        $this->assertDatabaseHas('ai_usage_logs', ['operation' => 'parse_job']);
    }

    public function test_nav_links_to_jobs(): void
    {
        $this->actingAs(User::factory()->create())->get('/perfil')->assertSee(route('jobs.index'), false);
    }
}
