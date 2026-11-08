<?php

namespace App\Services\Matching;

use App\Contracts\AiClient;
use App\Models\JobMatch;
use App\Models\JobPosting;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * O funil do match:
 *  1. filtros em SQL (vagas ativas, visíveis para a pessoa, internacionais só se ela aceita);
 *  2. similaridade entre o embedding do perfil e o de cada vaga, em PHP;
 *  3. nota final em código (MatchScorer) e gravação das melhores em job_matches.
 */
class MatchService
{
    public function __construct(private AiClient $ai, private MatchScorer $scorer)
    {
    }

    /** @return int quantas vagas foram gravadas como match */
    public function computeFor(User $user): int
    {
        $profile = $user->profile;
        if (! $profile || ! $this->hasEnoughData($profile)) {
            return 0;
        }

        $this->refreshProfileEmbedding($profile);
        $profileSkillIds = array_fill_keys($profile->skills()->pluck('skills.id')->all(), true);
        $keep = (int) config('faro.matching.keep');

        $scored = [];
        $hidden = 0;
        JobPosting::query()
            ->active()
            ->visibleTo($user)
            ->when(! $profile->accepts_international, fn ($q) => $q->where('is_international', false))
            ->with('skills')
            ->select('job_postings.*')
            ->chunkById(200, function ($jobs) use (&$scored, &$hidden, $profile, $profileSkillIds) {
                foreach ($jobs as $job) {
                    if (CareerFit::rejects($profile, $job)) {
                        $hidden++; // nível ou escolaridade fora do momento de carreira

                        continue;
                    }
                    $similarity = Vector::cosine($profile->embedding, $job->embedding);
                    $scored[$job->id] = [...$this->scorer->score($profile, $profileSkillIds, $job, $similarity), 'similarity' => $similarity];
                    unset($scored[$job->id]['parts']);
                }
            });

        uasort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $top = array_slice($scored, 0, $keep, true);

        DB::transaction(function () use ($user, $top) {
            $now = now();
            foreach ($top as $jobId => $r) {
                $user->jobMatches()->updateOrCreate(
                    ['job_posting_id' => $jobId],
                    [
                        'similarity' => $r['similarity'] !== null ? round($r['similarity'], 4) : null,
                        'score' => $r['score'],
                        'strengths' => $r['strengths'],
                        'gaps' => $r['gaps'],
                        'ai_model' => config('faro.ai.embedding_model'),
                        'evaluated_at' => $now,
                    ],
                );
            }

            // Saem os matches que caíram do topo, mas o que a pessoa avaliou fica guardado
            JobMatch::where('user_id', $user->id)
                ->whereNotIn('job_posting_id', array_keys($top) ?: [0])
                ->whereNull('feedback')
                ->delete();
        });

        $profile->forceFill(['matches_computed_at' => now(), 'matches_hidden' => $hidden])->save();

        return count($top);
    }

    /** Sem cargo, área ou habilidades não há o que comparar. */
    public function hasEnoughData(Profile $profile): bool
    {
        return filled($profile->desired_role) || filled($profile->area) || $profile->skills()->exists();
    }

    /** Gera de novo o embedding do perfil só quando o texto mudou. Sem IA, o match segue sem similaridade. */
    private function refreshProfileEmbedding(Profile $profile): void
    {
        $text = EmbeddingText::forProfile($profile);
        $hash = sha1($text);
        if ($profile->embedding && $profile->embedding_hash === $hash) {
            return;
        }

        try {
            [$vector] = $this->ai->embed([$text], 'embed_profile');
            $profile->forceFill(['embedding' => $vector, 'embedding_hash' => $hash])->save();
        } catch (\Throwable $e) {
            Log::warning('Embedding do perfil indisponível; match sem similaridade', ['profile_id' => $profile->id, 'erro' => $e->getMessage()]);
        }
    }
}
