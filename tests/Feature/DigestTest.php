<?php

namespace Tests\Feature;

use App\Models\JobPosting;
use App\Models\Profile;
use App\Models\User;
use App\Notifications\DailyDigestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DigestTest extends TestCase
{
    use RefreshDatabase;

    private function userWithMatches(array $profile = []): User
    {
        $user = User::factory()->create(['name' => 'Ana Souza']);
        Profile::factory()->for($user)->create(['digest_enabled' => true, 'last_digest_at' => now()->subDay(), ...$profile]);

        foreach ([['Nova boa', 90, 0], ['Nova média', 60, 0], ['Nova fraca', 40, 0], ['Antiga boa', 95, 3], ['Escondida', 88, 0], ['Salva', 87, 0]] as [$title, $score, $days]) {
            $job = JobPosting::factory()->create(['title' => $title]);
            $job->forceFill(['created_at' => $days ? now()->subDays($days) : now()->subHour()])->save();
            $user->jobMatches()->create(['job_posting_id' => $job->id, 'score' => $score, 'strengths' => [], 'gaps' => [],
                'dismissed_at' => $title === 'Escondida' ? now() : null]);
            if ($title === 'Salva') {
                $user->applications()->create(['job_posting_id' => $job->id]);
            }
        }

        return $user;
    }

    public function test_sends_only_new_good_unseen_jobs(): void
    {
        Notification::fake();
        $user = $this->userWithMatches();

        $this->artisan('faro:send-digests')->expectsOutputToContain('Resumos enviados: 1')->assertSuccessful();

        Notification::assertSentTo($user, DailyDigestNotification::class, function ($n) {
            return $n->matches->pluck('jobPosting.title')->all() === ['Nova boa', 'Nova média'];
        });
        $this->assertTrue($user->profile->fresh()->last_digest_at->isToday());

        // Rodar de novo no mesmo dia não repete as vagas
        $this->artisan('faro:send-digests')->expectsOutputToContain('Resumos enviados: 0');
        Notification::assertSentToTimes($user, DailyDigestNotification::class, 1);
    }

    public function test_respects_the_preference_and_verification(): void
    {
        Notification::fake();
        $this->userWithMatches(['digest_enabled' => false]);
        $unverified = $this->userWithMatches();
        $unverified->forceFill(['email_verified_at' => null])->save();

        $this->artisan('faro:send-digests')->expectsOutputToContain('Resumos enviados: 0');
        Notification::assertNothingSent();
    }

    public function test_email_content_and_language(): void
    {
        $user = $this->userWithMatches();
        $user->update(['locale' => 'en']);
        app()->setLocale('en');
        $matches = $user->jobMatches()->with('jobPosting')->orderByDesc('score')->limit(2)->get();

        $mail = (new DailyDigestNotification($matches))->toMail($user);

        $this->assertSame('Faro: 2 new jobs for you', $mail->subject);
        $this->assertSame('Hi, Ana!', $mail->greeting);
        $this->assertStringContainsString('/email/cancelar/'.$user->id, implode("\n", $mail->outroLines));
        $this->assertStringContainsString('signature=', implode("\n", $mail->outroLines));
    }

    public function test_unsubscribe_link_requires_signature(): void
    {
        $user = $this->userWithMatches();

        $this->get('/email/cancelar/'.$user->id)->assertForbidden();
        $this->assertTrue($user->profile->fresh()->digest_enabled);

        $this->get(URL::signedRoute('digest.unsubscribe', ['user' => $user->id]))->assertOk()->assertSee('Pronto, e-mail cancelado.');
        $this->assertFalse($user->profile->fresh()->digest_enabled);
    }
}
