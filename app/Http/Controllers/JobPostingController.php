<?php

namespace App\Http\Controllers;

use App\Enums\ContractType;
use App\Enums\JobLevel;
use App\Enums\JobSourceType;
use App\Enums\WorkModality;
use App\Jobs\StructureJobPosting;
use App\Models\JobPosting;
use App\Models\JobSource;
use App\Services\Jobs\JobClassifier;
use App\Services\Jobs\JobData;
use App\Support\Areas;
use App\Support\Brazil;
use App\Support\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Lista de vagas (com busca e filtros), detalhe e "adicionar vaga à mão".
 */
class JobPostingController extends Controller
{
    public const MANUAL_PER_DAY = 10;

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'area' => ['nullable', Rule::in(Areas::keys())],
            'nivel' => ['nullable', Rule::enum(JobLevel::class)],
            'modalidade' => ['nullable', Rule::enum(WorkModality::class)],
            'contrato' => ['nullable', Rule::enum(ContractType::class)],
            'pcd' => ['nullable', 'boolean'],
            'internacional' => ['nullable', Rule::in(['', 'sim', 'nao', 'so'])],
        ]);

        $user = $request->user();
        $international = $filters['internacional'] ?? (($user->profile?->accepts_international) ? 'sim' : 'nao');

        $jobs = JobPosting::query()
            ->active()
            ->visibleTo($user)
            ->with('source')
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
                $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('company', 'like', $like));
            })
            ->when($filters['area'] ?? null, fn ($q, $v) => $q->where('area', $v))
            ->when($filters['nivel'] ?? null, fn ($q, $v) => $q->where('level', $v))
            ->when($filters['modalidade'] ?? null, fn ($q, $v) => $q->where('modality', $v))
            ->when($filters['contrato'] ?? null, fn ($q, $v) => $q->where('contract_type', $v))
            ->when($request->boolean('pcd'), fn ($q) => $q->where('is_pcd', true))
            ->when($international === 'nao', fn ($q) => $q->where('is_international', false))
            ->when($international === 'so', fn ($q) => $q->where('is_international', true))
            ->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('jobs.index', [
            'jobs' => $jobs,
            'filters' => [...$filters, 'internacional' => $international],
            'areas' => Areas::options(),
            'levels' => JobLevel::cases(),
            'modalities' => WorkModality::cases(),
            'contracts' => ContractType::cases(),
            'lastFetch' => JobSource::max('last_fetched_at'),
        ]);
    }

    public function show(Request $request, JobPosting $job): View
    {
        abort_unless($job->user_id === null || $job->user_id === $request->user()->id, 404);

        return view('jobs.show', [
            'job' => $job->load(['source', 'skills']),
            'match' => $request->user()->jobMatches()->where('job_posting_id', $job->id)->first(),
            'application' => $request->user()->applications()->where('job_posting_id', $job->id)->first(),
        ]);
    }

    public function create(): View
    {
        return view('jobs.create', ['states' => Brazil::STATES]);
    }

    public function store(Request $request, JobClassifier $classifier): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:250'],
            'company' => ['nullable', 'string', 'max:250'],
            'url' => ['nullable', 'url:http,https', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', Rule::in(Brazil::STATES)],
            'remote' => ['nullable', 'boolean'],
            'description' => ['required', 'string', 'min:30', 'max:20000'],
        ]);

        $user = $request->user();
        $today = JobPosting::where('user_id', $user->id)->where('created_at', '>=', now()->startOfDay())->count();
        if ($today >= self::MANUAL_PER_DAY) {
            return back()->withInput()->withErrors(['title' => __('Você já adicionou :count vagas hoje. Tente de novo amanhã.', ['count' => self::MANUAL_PER_DAY])]);
        }

        $source = JobSource::firstOrCreate(
            ['slug' => 'manual'],
            ['name' => 'Adicionada pelo usuário', 'type' => JobSourceType::Manual],
        );

        $externalId = 'u'.$user->id.'-'.Str::lower(Str::random(12));
        $url = $data['url'] ?? null;
        $jobData = new JobData(
            externalId: $externalId,
            url: $url ?? '',
            title: trim($data['title']),
            description: trim($data['description']),
            company: $data['company'] ?? null,
            city: filled($data['city'] ?? null) ? trim($data['city']) : null,
            state: $data['state'] ?? null,
            remote: $request->boolean('remote'),
        );

        $job = JobPosting::create([
            'job_source_id' => $source->id,
            'user_id' => $user->id,
            'external_id' => $externalId,
            'url' => $url ?? '',
            'title' => $jobData->title,
            'company' => $jobData->company,
            'description' => $jobData->description,
            'city' => $jobData->city,
            'state' => $jobData->state ?? (Region::isGrandeSp($jobData->city) ? 'SP' : null),
            'published_at' => now(),
            'last_seen_at' => now(),
            'status' => 'active',
            'content_hash' => JobPosting::makeHash($url ?? 'manual:'.$externalId, $jobData->title, $jobData->company),
            ...$classifier->classify($jobData),
        ]);

        StructureJobPosting::dispatch($job)->onQueue('jobs');

        return redirect()->route('jobs.show', $job)
            ->with('status', __('Vaga adicionada! A IA vai completar área, nível e habilidades em alguns minutos.'));
    }

    public function destroy(Request $request, JobPosting $job): RedirectResponse
    {
        abort_unless($job->user_id !== null && $job->user_id === $request->user()->id, 404);
        $job->delete();

        return redirect()->route('jobs.index')->with('status', __('Vaga removida.'));
    }
}
