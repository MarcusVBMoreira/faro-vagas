<?php

namespace Tests\Feature;

use App\Enums\JobLevel;
use App\Models\JobPosting;
use App\Models\Profile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_renders_for_new_user(): void
    {
        $user = User::factory()->create(['name' => 'Ana Souza']);

        $this->actingAs($user)->get('/perfil')
            ->assertOk()
            ->assertSee('Olá, Ana')
            ->assertSee('Enviar e analisar');
    }

    public function test_user_can_edit_profile_and_skills(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create();
        $profile->skills()->attach(Skill::create(['name' => 'PHP']), ['source' => 'ai']);

        $this->actingAs($user)->put('/perfil', [
            'headline' => 'Auxiliar de enfermagem com 3 anos de experiência',
            'desired_role' => 'Técnica de enfermagem',
            'desired_level' => 'tecnico',
            'area' => 'saude',
            'desired_contracts' => ['clt', 'temporario'],
            'salary_expectation' => 3200,
            'availability' => 'imediata',
            'open_to_work' => '1',
            'city' => 'Santo André',
            'state' => 'SP',
            'phone' => '(11) 91234-5678',
            'linkedin_url' => 'https://www.linkedin.com/in/ana',
            'driver_license' => ['B'],
            'desired_modalities' => ['presencial'],
            'education_level' => 'tecnico',
            'current_semester' => 4,
            'graduation_year' => 2029,
            'languages' => [
                ['language' => 'ingles', 'level' => 'avancado'],
                ['language' => 'libras', 'level' => 'basico'],
                ['language' => '', 'level' => ''],
            ],
            'pcd' => 'nao',
            'skills' => 'PHP, laravel, node, C#',
            'digest_enabled' => '1',
        ])->assertRedirect(route('profile.show'))->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertSame(JobLevel::Tecnico, $profile->desired_level);
        $this->assertSame('saude', $profile->area);
        $this->assertSame('Técnica de enfermagem', $profile->desired_role);
        $this->assertSame(['clt', 'temporario'], $profile->desired_contracts);
        $this->assertSame(['presencial'], $profile->desired_modalities);
        $this->assertSame(['B'], $profile->driver_license);
        $this->assertSame(4, $profile->current_semester);
        $this->assertSame(\App\Enums\EducationLevel::Tecnico, $profile->education_level);
        $this->assertEquals([['language' => 'ingles', 'level' => 'avancado'], ['language' => 'libras', 'level' => 'basico']], $profile->languages); // o JSON do MySQL reordena as chaves
        $this->assertSame('avancado', $profile->english_level);
        $this->assertTrue($profile->open_to_work);
        $this->assertFalse($profile->is_pcd);
        $this->assertNotNull($profile->pcd_consent_at);

        $skills = $profile->skills->keyBy('name');
        $this->assertEqualsCanonicalizing(['PHP', 'laravel', 'Node.js', 'C#'], $skills->keys()->all());
        $this->assertSame('ai', $skills['PHP']->pivot->source);        // mantém a origem
        $this->assertSame('manual', $skills['Node.js']->pivot->source);
        $this->assertSame('c-sharp', $skills['C#']->slug);
    }

    public function test_existing_skill_is_reused_regardless_of_case(): void
    {
        $this->seed(\Database\Seeders\SkillSeeder::class);
        $user = User::factory()->create();
        Profile::factory()->for($user)->create();

        $this->actingAs($user)->put('/perfil', ['skills' => 'laravel, MYSQL']);

        $this->assertSame(1, Skill::where('slug', 'laravel')->count());
        $this->assertEqualsCanonicalizing(['Laravel', 'MySQL'], $user->profile->skills->pluck('name')->all());
    }

    public function test_invalid_values_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put('/perfil', [
            'desired_level' => 'chefe',
            'state' => 'ZZ',
            'desired_modalities' => ['lua'],
            'current_semester' => 40,
            'area' => 'back-end',
            'desired_contracts' => ['escravo'],
            'linkedin_url' => 'https://example.com/ana',
            'phone' => 'abc',
            'languages' => [['language' => 'elfico', 'level' => 'fluente']],
        ])->assertSessionHasErrors([
            'desired_level', 'state', 'desired_modalities.0', 'current_semester',
            'area', 'desired_contracts.0', 'linkedin_url', 'phone', 'languages.0.language',
        ]);
    }

    public function test_pcd_is_only_stored_with_explicit_choice(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create(['is_pcd' => true, 'pcd_consent_at' => now()]);

        $this->actingAs($user)->put('/perfil', ['pcd' => ''])->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertNull($profile->is_pcd);
        $this->assertNull($profile->pcd_consent_at);
    }

    public function test_profile_page_shows_all_sections_and_completeness(): void
    {
        $user = User::factory()->create();
        Profile::factory()->for($user)->create(['area' => 'logistica']);

        $this->actingAs($user)->get('/perfil')
            ->assertOk()
            ->assertSee('Objetivo profissional')
            ->assertSee('Experiências')
            ->assertSee('Formação')
            ->assertSee('Idiomas')
            ->assertSee('Logística, estoque e transporte')
            ->assertSee('Verificação em duas etapas por e-mail')
            ->assertSee('Perfil completo');
    }

    public function test_unverified_user_is_sent_to_email_verification(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/perfil')->assertRedirect(route('verification.notice'));
    }

    public function test_account_deletion_requires_email_and_removes_everything(): void
    {
        Storage::fake('local');
        Queue::fake();
        $user = User::factory()->create(['email' => 'ana@exemplo.com']);
        Profile::factory()->for($user)->create();
        $this->actingAs($user)->post('/perfil/curriculo', [
            'resume' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ]);
        $path = $user->currentResume->storage_path;
        $user->applications()->create(['job_posting_id' => JobPosting::factory()->create()->id]);

        $this->actingAs($user)->delete('/conta', ['confirm_email' => 'outro@exemplo.com'])
            ->assertSessionHasErrors('confirm_email');
        $this->assertNotNull($user->fresh());

        $this->actingAs($user)->delete('/conta', ['confirm_email' => 'ana@exemplo.com'])
            ->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNull(User::find($user->id));
        $this->assertDatabaseCount('profiles', 0);
        $this->assertDatabaseCount('resumes', 0);
        $this->assertDatabaseCount('applications', 0);
        Storage::disk('local')->assertMissing($path);
    }
}
