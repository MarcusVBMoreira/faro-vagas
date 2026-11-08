<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Quadro (Kanban) de candidaturas: salvar vaga, mover entre etapas, anotar.
 * Funciona arrastando (SortableJS) e também sem JavaScript, pelo seletor de etapa.
 */
class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $applications = $request->user()->applications()
            ->with('jobPosting.source')
            ->orderBy('position')->orderByDesc('updated_at')
            ->get()
            ->groupBy(fn (Application $a) => $a->status->value);

        return view('applications.index', [
            'columns' => ApplicationStatus::cases(),
            'applications' => $applications,
            'total' => $applications->flatten()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['job_posting_id' => ['required', 'integer']]);
        $job = JobPosting::visibleTo($request->user())->findOrFail($data['job_posting_id']);

        $application = $request->user()->applications()->firstOrCreate(
            ['job_posting_id' => $job->id],
            ['status' => ApplicationStatus::Saved, 'position' => 0],
        );

        return back()->with('status', $application->wasRecentlyCreated
            ? __('Vaga salva no seu quadro de candidaturas.')
            : __('Essa vaga já está no seu quadro.'));
    }

    public function update(Request $request, Application $application): RedirectResponse|JsonResponse
    {
        $this->authorizeOwner($request, $application);
        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(ApplicationStatus::class)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        if (isset($data['status'])) {
            $data['position'] = 0; // vai para o topo da nova coluna
            $this->markApplied($application, ApplicationStatus::from($data['status']));
        }
        $application->update($data);

        return $request->expectsJson()
            ? response()->json(['ok' => true])
            : back()->with('status', __('Candidatura atualizada.'));
    }

    /** Arrastar e soltar: recebe a nova ordem de uma coluna inteira. */
    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'ids' => ['present', 'array', 'max:500'],
            'ids.*' => ['integer'],
        ]);
        $status = ApplicationStatus::from($data['status']);

        DB::transaction(function () use ($request, $data, $status) {
            $owned = $request->user()->applications()->whereIn('id', $data['ids'])->get()->keyBy('id');
            foreach (array_values($data['ids']) as $position => $id) {
                if ($application = $owned->get($id)) {
                    $this->markApplied($application, $status);
                    $application->update(['status' => $status, 'position' => $position]);
                }
            }
        });

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeOwner($request, $application);
        $application->delete();

        return back()->with('status', __('Vaga removida do quadro.'));
    }

    private function markApplied(Application $application, ApplicationStatus $status): void
    {
        if ($status !== ApplicationStatus::Saved && $status !== ApplicationStatus::Rejected && ! $application->applied_at) {
            $application->applied_at = now();
        }
    }

    private function authorizeOwner(Request $request, Application $application): void
    {
        abort_unless($application->user_id === $request->user()->id, 404);
    }
}
