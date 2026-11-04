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
            'headline' => 'Dev back-end em formação',
            'desired_level' => 'junior',
            'area' => 'back-end',
            'city' => 'Santo André',
            'state' => 'SP',
            'desired_modalities' => ['remoto'],
            'current_semester' => 4,
            'graduation_year' => 2029,
            'english_level' => 'avancado',
            'skills' => 'PHP, laravel, node, C#',
            'digest_enabled' => '1',
        ])->assertRedirect(route('profile.show'))->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertSame(JobLevel::Junior, $profile->desired_level);
        $this->assertSame(['remoto'], $profile->desired_modalities);
        $this->assertSame(4, $profile->current_semester);

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
        ])->assertSessionHasErrors(['desired_level', 'state', 'desired_modalities.0', 'current_semester']);
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
