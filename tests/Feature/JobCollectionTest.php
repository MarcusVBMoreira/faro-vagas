<?php

namespace Tests\Feature;

use App\Enums\JobSourceType;
use App\Jobs\StructureJobPosting;
use App\Models\JobPosting;
use App\Models\JobSource;
use Database\Seeders\JobSourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JobCollectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seed(JobSourceSeeder::class);
        // Uma busca por fonte nos testes (em produção são várias: "", "remoto", "home office")
        JobSource::where('slug', 'adzuna-br')->first()->update(['config' => ['country' => 'br', 'where' => 'São Paulo', 'queries' => [''], 'pages' => 1]]);
        JobSource::where('slug', 'jooble-br')->first()->update(['config' => ['location' => 'São Paulo', 'keywords' => [''], 'pages' => 1]]);
        config(['faro.jobs.adzuna.app_id' => 'id-teste', 'faro.jobs.adzuna.app_key' => 'chave-teste', 'faro.jobs.jooble.api_key' => 'jooble-teste']);
    }

    private function adzunaJob(array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => '4512',
            'title' => 'Auxiliar Administrativo <strong>Júnior</strong>',
            'description' => 'Rotinas administrativas, Excel e atendimento. Contratação CLT.',
            'redirect_url' => 'https://www.adzuna.com.br/land/ad/4512',
            'created' => '2026-11-04T10:00:00Z',
            'company' => ['display_name' => 'Acme Ltda'],
            'location' => ['display_name' => 'Santo André, São Paulo', 'area' => ['Brasil', 'São Paulo', 'Santo André']],
            'category' => ['label' => 'Administração'],
            'contract_type' => 'permanent',
        ], $overrides);
    }

    /** Roda a coleta e devolve a saída inteira (mensagens de falha mostram tudo). */
    private function fetch(array $sources): string
    {
        $this->withoutMockingConsoleOutput();
        $this->assertSame(0, \Illuminate\Support\Facades\Artisan::call('faro:fetch-jobs', ['--source' => $sources]));

        return \Illuminate\Support\Facades\Artisan::output();
    }

    private function fakeSources(array $adzuna, array $jooble = [], array $remotive = [], array $github = []): void
    {
        Http::fake([
            'api.adzuna.com/*' => Http::response(['results' => $adzuna]),
            'jooble.org/*' => Http::response(['totalCount' => count($jooble), 'jobs' => $jooble]),
            'remotive.com/*' => Http::response(['jobs' => $remotive]),
            'api.github.com/*' => Http::response($github),
        ]);
    }

    public function test_collects_adzuna_jobs_in_greater_sp_and_skips_other_regions(): void
    {
        $this->fakeSources([
            $this->adzunaJob(),
            $this->adzunaJob(['id' => '1', 'redirect_url' => 'https://x/1', 'title' => 'Vendedor', 'location' => ['display_name' => 'Campinas, São Paulo', 'area' => ['Brasil', 'São Paulo', 'Campinas']]]),
            $this->adzunaJob(['id' => '2', 'redirect_url' => 'https://x/2', 'title' => 'Atendente home office', 'location' => ['display_name' => 'Recife, Pernambuco', 'area' => ['Brasil', 'Pernambuco', 'Recife']]]),
        ]);

        $output = $this->fetch(['adzuna-br']);
        $this->assertStringContainsString('novas 2', $output);
        $this->assertStringContainsString('fora da região 1', $output);
        $this->assertStringNotContainsString('chave-teste', $output);

        $job = JobPosting::where('external_id', '4512')->firstOrFail();
        $this->assertSame('Auxiliar Administrativo Júnior', $job->title); // HTML removido
        $this->assertSame(['Santo André', 'SP'], [$job->city, $job->state]);
        $this->assertSame('administracao', $job->area);
        $this->assertSame('clt', $job->contract_type);
        $this->assertSame('junior', $job->level->value);
        $this->assertSame('presencial', $job->modality->value);
        $this->assertFalse($job->is_international);

        $remote = JobPosting::where('external_id', '2')->firstOrFail();
        $this->assertSame('remoto', $remote->modality->value);

        Queue::assertPushedOn('jobs', StructureJobPosting::class);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'api.adzuna.com/v1/api/jobs/br/search/1') && $r['where'] === 'São Paulo');
    }

    public function test_running_twice_does_not_duplicate_and_renews_jobs(): void
    {
        $this->fakeSources([$this->adzunaJob()]);

        $this->artisan('faro:fetch-jobs', ['--source' => ['adzuna-br']])->assertSuccessful();
        $this->artisan('faro:fetch-jobs', ['--source' => ['adzuna-br']])->expectsOutputToContain('renovadas 1')->assertSuccessful();

        $this->assertSame(1, JobPosting::count());
    }

    public function test_same_job_from_two_aggregators_is_stored_once(): void
    {
        $this->fakeSources([$this->adzunaJob(['title' => 'Auxiliar Administrativo'])], [[
            'id' => 99, 'title' => 'Auxiliar Administrativo', 'company' => 'Acme Ltda', 'location' => 'Santo André - SP',
            'snippet' => 'Rotinas administrativas.', 'link' => 'https://br.jooble.org/desc/99', 'updated' => '2026-11-04T08:00:00',
        ]]);

        $this->artisan('faro:fetch-jobs', ['--source' => ['adzuna-br', 'jooble-br']])
            ->expectsOutputToContain('repetidas 1')
            ->assertSuccessful();

        $this->assertSame(1, JobPosting::count());
    }

    public function test_jobs_that_disappear_are_closed(): void
    {
        $source = JobSource::where('slug', 'adzuna-br')->first();
        $old = JobPosting::factory()->create(['job_source_id' => $source->id, 'external_id' => 'sumiu', 'last_seen_at' => now()->subDays(10)]);
        $this->fakeSources([$this->adzunaJob()]);

        $this->artisan('faro:fetch-jobs', ['--source' => ['adzuna-br']])->expectsOutputToContain('fechadas 1')->assertSuccessful();

        $this->assertSame('closed', $old->fresh()->status);
    }

    public function test_sources_without_keys_are_skipped(): void
    {
        config(['faro.jobs.adzuna.app_id' => null, 'faro.jobs.jooble.api_key' => null]);
        $this->fakeSources([]);

        $this->artisan('faro:fetch-jobs', ['--source' => ['adzuna-br', 'jooble-br']])
            ->expectsOutputToContain('pulada')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_a_failing_source_does_not_stop_the_others(): void
    {
        Http::fake([
            'api.adzuna.com/*' => Http::response('erro', 500),
            'remotive.com/*' => Http::response(['jobs' => []]),
        ]);

        $this->artisan('faro:fetch-jobs', ['--source' => ['adzuna-br', 'remotive']])
            ->expectsOutputToContain('falhou')
            ->expectsOutputToContain('remotive')
            ->doesntExpectOutputToContain('chave-teste')
            ->assertSuccessful();
    }

    public function test_remotive_keeps_only_international_jobs_open_to_brazil(): void
    {
        $this->fakeSources([], [], [
            ['id' => 1, 'url' => 'https://remotive.com/1', 'title' => 'Customer Support Specialist', 'company_name' => 'Acme',
                'category' => 'Customer Service', 'job_type' => 'full_time', 'publication_date' => '2026-11-03T10:00:00',
                'candidate_required_location' => 'Worldwide', 'description' => '<p>Fluent <b>English</b>. Zendesk.</p>'],
            ['id' => 2, 'url' => 'https://remotive.com/2', 'title' => 'Sales Rep', 'company_name' => 'Beta',
                'candidate_required_location' => 'USA Only', 'description' => 'US only'],
        ]);

        $this->artisan('faro:fetch-jobs', ['--source' => ['remotive']])->expectsOutputToContain('novas 1')->assertSuccessful();

        $job = JobPosting::sole();
        $this->assertTrue($job->is_international);
        $this->assertSame('remoto', $job->modality->value);
        $this->assertSame('atendimento', $job->area);
        $this->assertSame("Fluent English. Zendesk.", $job->description);
    }

    public function test_github_issues_connector(): void
    {
        $this->fakeSources([], [], [], [
            ['number' => 7, 'title' => '[São Paulo] Desenvolvedor PHP Pleno na Acme', 'html_url' => 'https://github.com/backend-br/vagas/issues/7',
                'body' => 'Laravel, MySQL. Híbrido.', 'labels' => [['name' => 'CLT'], ['name' => 'Pleno']], 'created_at' => '2026-11-02T10:00:00Z'],
            ['number' => 8, 'title' => '[Remoto] Dev Go', 'html_url' => 'https://github.com/backend-br/vagas/issues/8', 'body' => 'Go', 'labels' => [['name' => 'Remoto'], ['name' => 'PJ']]],
            ['number' => 9, 'title' => 'PR qualquer', 'html_url' => 'x', 'pull_request' => []],
        ]);

        $this->artisan('faro:fetch-jobs', ['--source' => ['backend-br']])->assertSuccessful();

        $sp = JobPosting::where('external_id', '7')->firstOrFail();
        $this->assertSame('Desenvolvedor PHP Pleno na Acme', $sp->title);
        $this->assertSame('Acme', $sp->company);
        $this->assertSame(['São Paulo', 'SP'], [$sp->city, $sp->state]);
        $this->assertSame('hibrido', $sp->modality->value);
        $this->assertSame('clt', $sp->contract_type);
        $this->assertSame('tecnologia', $sp->area);

        $this->assertSame('pj', JobPosting::where('external_id', '8')->value('contract_type'));
        $this->assertSame(2, JobPosting::count());
    }

    public function test_manual_source_is_never_fetched(): void
    {
        $this->assertSame(JobSourceType::Manual, JobSource::where('slug', 'manual')->value('type'));
        Http::fake();

        $this->artisan('faro:fetch-jobs', ['--source' => ['manual']])->assertSuccessful();
        Http::assertNothingSent();
    }
}
