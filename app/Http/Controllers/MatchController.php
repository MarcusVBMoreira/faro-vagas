<?php

namespace App\Http\Controllers;

use App\Jobs\ComputeMatches;
use App\Models\JobMatch;
use App\Services\Matching\MatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Vagas para você": as vagas mais compatíveis com o perfil, com nota, pontos fortes e o que falta.
 */
class MatchController extends Controller
{
    public const SHOW = 20;

    public function index(Request $request, MatchService $service): View
    {
        $user = $request->user();
        $profile = $user->profile;
        $hasData = $profile && $service->hasEnoughData($profile);

        // Primeira visita (ou perfil novo): calcula em segundo plano
        if ($hasData && ! $profile->matches_computed_at) {
            ComputeMatches::dispatch($user);
            $profile->refresh();
        }

        $matches = $user->jobMatches()
            ->whereNull('dismissed_at')
            ->whereHas('jobPosting', fn ($q) => $q->active())
            ->with(['jobPosting.source'])
            ->orderByDesc('score')->orderByDesc('id')
            ->limit(self::SHOW)
            ->get();

        return view('matches.index', [
            'matches' => $matches,
            'hasData' => $hasData,
            'computedAt' => $profile?->matches_computed_at,
            'hidden' => $profile?->matches_hidden,
        ]);
    }

    public function feedback(Request $request, JobMatch $match): RedirectResponse
    {
        abort_unless($match->user_id === $request->user()->id, 404);
        $data = $request->validate(['feedback' => ['required', Rule::in(['good', 'bad'])]]);

        $match->update([
            'feedback' => $data['feedback'],
            'dismissed_at' => $data['feedback'] === 'bad' ? now() : null,
        ]);

        return back()->with('status', $data['feedback'] === 'good'
            ? __('Anotado! Vamos usar isso para melhorar suas recomendações.')
            : __('Vaga escondida. Ela não aparece mais na sua lista.'));
    }

    public function refresh(Request $request): RedirectResponse
    {
        ComputeMatches::dispatch($request->user());

        return back()->with('status', __('Recalculando suas vagas. Atualize a página em alguns instantes.'));
    }
}
