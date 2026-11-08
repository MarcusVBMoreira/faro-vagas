<?php

namespace App\Services\Matching;

use App\Models\User;
use App\Notifications\DailyDigestNotification;
use Illuminate\Support\Facades\Log;

/**
 * Monta e envia o e-mail diário: até 5 vagas novas (desde o último e-mail) com nota boa,
 * que a pessoa não escondeu nem já salvou. Sem vaga nova boa, não manda nada.
 */
class DigestSender
{
    public const MAX_JOBS = 5;

    public const MIN_SCORE = 55;

    /** @return int quantas vagas foram no e-mail (0 = não enviou) */
    public function sendTo(User $user): int
    {
        $profile = $user->profile;
        if (! $profile?->digest_enabled || ! $user->hasVerifiedEmail()) {
            return 0;
        }

        $since = $profile->last_digest_at ?? now()->subDay();
        $saved = $user->applications()->pluck('job_posting_id');

        $matches = $user->jobMatches()
            ->whereNull('dismissed_at')
            ->where('score', '>=', self::MIN_SCORE)
            ->whereNotIn('job_posting_id', $saved)
            ->whereHas('jobPosting', fn ($q) => $q->active()->where('created_at', '>', $since))
            ->with('jobPosting')
            ->orderByDesc('score')
            ->limit(self::MAX_JOBS)
            ->get();

        if ($matches->isEmpty()) {
            return 0;
        }

        try {
            $user->notify(new DailyDigestNotification($matches));
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }

        $profile->forceFill(['last_digest_at' => now()])->save();
        Log::info('Resumo diário enviado', ['user_id' => $user->id, 'vagas' => $matches->count()]);

        return $matches->count();
    }
}
