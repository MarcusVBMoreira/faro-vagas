<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\JobLevel;
use App\Models\Application;
use App\Models\JobPosting;
use App\Models\Profile;
use App\Models\Skill;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_create_skills_and_sources(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('skills', ['slug' => 'laravel']);
        $this->assertDatabaseHas('job_sources', ['slug' => 'backend-br']);
    }

    public function test_profile_belongs_to_user_and_has_skills(): void
    {
        $profile = Profile::factory()->create(['desired_level' => JobLevel::Estagio]);
        $php = Skill::create(['name' => 'PHP']);
        $profile->skills()->attach($php, ['proficiency' => 3, 'source' => 'manual']);

        $user = $profile->user->fresh();

        $this->assertSame(JobLevel::Estagio, $user->profile->desired_level);
        $this->assertSame('php', $user->profile->skills->first()->slug);
        $this->assertSame(3, (int) $user->profile->skills->first()->pivot->proficiency);
    }

    public function test_duplicate_job_posting_hash_is_rejected(): void
    {
        $job = JobPosting::factory()->create();

        $this->expectException(QueryException::class);

        JobPosting::factory()->create(['content_hash' => $job->content_hash]);
    }

    public function test_application_status_changes_are_logged(): void
    {
        $user = User::factory()->create();
        $job = JobPosting::factory()->create();

        $application = $user->applications()->create(['job_posting_id' => $job->id]);
        $application->update(['status' => ApplicationStatus::Interview]);

        $events = $application->events()->orderBy('id')->get();

        $this->assertCount(2, $events);
        $this->assertSame('saved', $events[0]->to_status);
        $this->assertSame('saved', $events[1]->from_status);
        $this->assertSame('interview', $events[1]->to_status);
        $this->assertInstanceOf(Application::class, $events[1]->application);
    }

    public function test_deleting_user_removes_their_data(): void
    {
        $profile = Profile::factory()->create();
        $job = JobPosting::factory()->create();
        $profile->user->applications()->create(['job_posting_id' => $job->id]);

        $profile->user->delete();

        $this->assertDatabaseCount('profiles', 0);
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('job_postings', 1);
    }
}
