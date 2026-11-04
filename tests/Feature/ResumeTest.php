<?php

namespace Tests\Feature;

use App\Enums\JobLevel;
use App\Jobs\AnalyzeResume;
use App\Models\Profile;
use App\Models\Resume;
use App\Models\Skill;
use App\Models\User;
use App\Services\Resume\PdfTextExtractor;
use App\Services\Resume\UnreadablePdfException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResumeTest extends TestCase
{
    use RefreshDatabase;

    private const RESUME_TEXT = "Ana Souza - Mauá/SP\nEngenharia de Software, 3º semestre\nPHP, JavaScript, MySQL, Git\nInglês intermediário";

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function fakeExtractor(?string $text = self::RESUME_TEXT): void
    {
        $this->app->instance(PdfTextExtractor::class, new class($text) extends PdfTextExtractor
        {
            public function __construct(private ?string $text)
            {
            }

            public function extract(string $path): string
            {
                if ($this->text === null) {
                    throw new UnreadablePdfException('sem texto');
                }

                return $this->text;
            }
        });
    }

    private function fakeAi(array $answer): void
    {
        Http::fake([
            'ia.test/api/generate' => Http::response([
                'response' => json_encode($answer),
                'prompt_eval_count' => 400,
                'eval_count' => 120,
            ]),
        ]);
    }

    private function aiAnswer(array $overrides = []): array
    {
        return array_merge([
            'titulo' => 'Estudante de Engenharia de Software focado em web',
            'resumo' => 'Busca estágio em desenvolvimento web.',
            'nivel_desejado' => 'estagio',
            'area' => 'Desenvolvimento Web',
            'cidade' => 'Mauá/SP',
            'estado' => '',
            'semestre_atual' => 3,
            'ano_conclusao' => 2029,
            'ingles' => 'intermediario',
            'modalidades_desejadas' => ['estagio', 'hibrido', 'remoto'],
            'habilidades' => ['PHP', 'js', 'MySQL', 'Git'],
        ], $overrides);
    }

    public function test_upload_stores_pdf_and_queues_analysis(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/perfil/curriculo', ['resume' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf')])
            ->assertRedirect(route('profile.show'));

        $resume = $user->currentResume;
        $this->assertNotNull($resume);
        $this->assertSame('cv.pdf', $resume->original_name);
        $this->assertSame(Resume::STATUS_PENDING, $resume->status);
        Storage::disk('local')->assertExists($resume->storage_path);
        Queue::assertPushed(AnalyzeResume::class);
    }

    public function test_upload_rejects_non_pdf_and_big_files(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/perfil/curriculo', ['resume' => UploadedFile::fake()->create('cv.docx', 100)])
            ->assertSessionHasErrors('resume');

        $this->actingAs($user)
            ->post('/perfil/curriculo', ['resume' => UploadedFile::fake()->create('cv.pdf', 6000, 'application/pdf')])
            ->assertSessionHasErrors('resume');

        Queue::assertNothingPushed();
    }

    public function test_new_upload_replaces_current_resume(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        foreach (['primeiro.pdf', 'segundo.pdf'] as $name) {
            $this->actingAs($user)->post('/perfil/curriculo', [
                'resume' => UploadedFile::fake()->create($name, 100, 'application/pdf'),
            ]);
        }

        $this->assertSame(1, $user->resumes()->where('is_current', true)->count());
        $this->assertSame('segundo.pdf', $user->fresh()->currentResume->original_name);
    }

    public function test_analysis_fills_profile_with_normalized_data(): void
    {
        $this->fakeExtractor();
        $this->fakeAi($this->aiAnswer());
        $user = User::factory()->create();

        $this->actingAs($user)->post('/perfil/curriculo', [
            'resume' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ]);

        $resume = $user->fresh()->currentResume;
        $this->assertSame(Resume::STATUS_DONE, $resume->status);
        $this->assertNotNull($resume->parsed_at);

        $profile = $user->fresh()->profile;
        $this->assertSame(JobLevel::Estagio, $profile->desired_level);
        $this->assertSame('Mauá', $profile->city);
        $this->assertSame('SP', $profile->state);
        $this->assertSame(3, $profile->current_semester);
        $this->assertSame(['hibrido', 'remoto'], $profile->desired_modalities);
        $this->assertEqualsCanonicalizing(['PHP', 'JavaScript', 'MySQL', 'Git'], $profile->skills->pluck('name')->all());
        $this->assertTrue($profile->skills->every(fn ($s) => $s->pivot->source === 'ai'));

        // O pedido levou o token e foi registrado para controle de uso.
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer token-de-teste')
            && $request['model'] === 'qwen2.5:3b'
            && is_array($request['format']));
        $this->assertDatabaseHas('ai_usage_logs', ['user_id' => $user->id, 'operation' => 'parse_resume', 'input_tokens' => 400]);
    }

    public function test_manual_skills_survive_a_new_analysis(): void
    {
        $this->fakeExtractor();
        $this->fakeAi($this->aiAnswer(['habilidades' => ['Python']]));
        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create();
        $profile->skills()->attach(Skill::create(['name' => 'Docker']), ['source' => 'manual']);
        $profile->skills()->attach(Skill::create(['name' => 'Cobol']), ['source' => 'ai']);

        $this->actingAs($user)->post('/perfil/curriculo', [
            'resume' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ]);

        $names = $profile->fresh()->skills->pluck('name')->all();
        $this->assertEqualsCanonicalizing(['Docker', 'Python'], $names);
    }

    public function test_unreadable_pdf_fails_with_helpful_message(): void
    {
        $this->fakeExtractor(null);
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/perfil/curriculo', [
            'resume' => UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf'),
        ]);

        $resume = $user->fresh()->currentResume;
        $this->assertSame(Resume::STATUS_FAILED, $resume->status);
        $this->assertStringContainsString('escaneada', $resume->error_message);
        Http::assertNothingSent();
    }

    public function test_ai_offline_marks_resume_as_failed_after_retries(): void
    {
        $this->fakeExtractor();
        Http::fake(['ia.test/*' => Http::response('erro', 502)]);
        $user = User::factory()->create();

        $resume = $user->resumes()->create([
            'original_name' => 'cv.pdf', 'storage_path' => 'resumes/x.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 10,
        ]);

        $job = new AnalyzeResume($resume);
        $job->tries = 1; // sem nova tentativa no teste
        $this->app->call([$job, 'handle']);

        $resume->refresh();
        $this->assertSame(Resume::STATUS_FAILED, $resume->status);
        $this->assertStringContainsString('indisponível', $resume->error_message);
    }

    public function test_daily_upload_limit(): void
    {
        Queue::fake();
        config(['faro.resumes.max_uploads_per_day' => 2]);
        $user = User::factory()->create();

        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($user)->post('/perfil/curriculo', [
                'resume' => UploadedFile::fake()->create("cv{$i}.pdf", 100, 'application/pdf'),
            ])->assertSessionHasNoErrors();
        }

        $this->actingAs($user)->post('/perfil/curriculo', [
            'resume' => UploadedFile::fake()->create('cv3.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('resume');
    }

    public function test_status_endpoint(): void
    {
        $user = User::factory()->create();
        $user->resumes()->create([
            'original_name' => 'cv.pdf', 'storage_path' => 'x', 'mime_type' => 'application/pdf',
            'size_bytes' => 1, 'status' => Resume::STATUS_PROCESSING,
        ]);

        $this->actingAs($user)->getJson('/perfil/curriculo/status')
            ->assertOk()
            ->assertJson(['status' => 'processing', 'finished' => false]);
    }
}
