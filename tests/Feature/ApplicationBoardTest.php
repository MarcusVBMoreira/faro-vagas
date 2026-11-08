<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_saves_a_job_once_and_sees_it_on_the_board(): void
    {
        $user = User::factory()->create();
        $job = JobPosting::factory()->create(['title' => 'Estágio em Marketing']);

        $this->actingAs($user)->post('/candidaturas', ['job_posting_id' => $job->id])
            ->assertSessionHas('status', 'Vaga salva no seu quadro de candidaturas.');
        $this->actingAs($user)->post('/candidaturas', ['job_posting_id' => $job->id])
            ->assertSessionHas('status', 'Essa vaga já está no seu quadro.');

        $this->assertSame(1, $user->applications()->count());
        $this->actingAs($user)->get('/candidaturas')->assertOk()->assertSee('Estágio em Marketing')->assertSee('Salva');
        $this->actingAs($user)->get('/vagas/'.$job->id)->assertSee('No seu quadro: Salva');
    }

    public function test_cannot_save_another_users_private_job(): void
    {
        $private = JobPosting::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->actingAs(User::factory()->create())->post('/candidaturas', ['job_posting_id' => $private->id])->assertNotFound();
    }

    public function test_moving_a_card_records_history_and_application_date(): void
    {
        $user = User::factory()->create();
        $application = $user->applications()->create(['job_posting_id' => JobPosting::factory()->create()->id]);

        $this->actingAs($user)->patch('/candidaturas/'.$application->id, ['status' => 'interview'])->assertSessionHasNoErrors();

        $application->refresh();
        $this->assertSame('interview', $application->status->value);
        $this->assertNotNull($application->applied_at);
        $this->assertSame(['saved', 'interview'], $application->events()->orderBy('id')->pluck('to_status')->all());
    }

    public function test_drag_and_drop_reorders_a_column(): void
    {
        $user = User::factory()->create();
        [$a, $b] = [
            $user->applications()->create(['job_posting_id' => JobPosting::factory()->create()->id]),
            $user->applications()->create(['job_posting_id' => JobPosting::factory()->create()->id]),
        ];
        $foreign = Application::forceCreate(['user_id' => User::factory()->create()->id, 'job_posting_id' => JobPosting::factory()->create()->id]);

        $this->actingAs($user)->postJson('/candidaturas/ordenar', ['status' => 'applied', 'ids' => [$b->id, $a->id, $foreign->id]])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(['applied', 0], [$b->fresh()->status->value, $b->fresh()->position]);
        $this->assertSame(['applied', 1], [$a->fresh()->status->value, $a->fresh()->position]);
        $this->assertSame('saved', $foreign->fresh()->status->value); // de outra pessoa: intocada

        $this->actingAs($user)->postJson('/candidaturas/ordenar', ['status' => 'chefe', 'ids' => []])->assertUnprocessable();
    }

    public function test_notes_and_removal(): void
    {
        $user = User::factory()->create();
        $application = $user->applications()->create(['job_posting_id' => JobPosting::factory()->create()->id]);

        $this->actingAs($user)->patch('/candidaturas/'.$application->id, ['notes' => 'Entrevista dia 12'])->assertSessionHasNoErrors();
        $this->assertSame('Entrevista dia 12', $application->fresh()->notes);

        $this->actingAs(User::factory()->create())->delete('/candidaturas/'.$application->id)->assertNotFound();
        $this->actingAs($user)->delete('/candidaturas/'.$application->id);
        $this->assertModelMissing($application);
    }

    public function test_board_labels_are_translated(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $this->actingAs($user)->get('/candidaturas')->assertSee('Interview')->assertSee('Your board is empty.');
    }
}
