<?php

namespace App\Services\Jobs;

use App\Jobs\StructureJobPosting;
use App\Models\JobPosting;
use App\Models\JobSource;
use App\Support\Region;
use Illuminate\Support\Str;

/**
 * Coleta as vagas de uma fonte: filtra a região (Grande SP + remotas), evita duplicadas,
 * grava, renova as que continuam no ar e fecha as que sumiram.
 */
class JobCollector
{
    public function __construct(private JobConnectors $connectors, private JobClassifier $classifier)
    {
    }

    /**
     * @return array{fetched: int, created: int, updated: int, outside_region: int, duplicated: int, closed: int}
     */
    public function collect(JobSource $source): array
    {
        $stats = ['fetched' => 0, 'created' => 0, 'updated' => 0, 'outside_region' => 0, 'duplicated' => 0, 'closed' => 0];
        $connector = $this->connectors->for($source->type);

        if (! $connector || ! $connector->isConfigured()) {
            return $stats;
        }

        $maxNew = (int) config('faro.jobs.max_new_per_source');

        foreach ($connector->fetch($source) as $job) {
            $stats['fetched']++;

            if (! $job->international && ! Region::accepts($job->city, $job->state, $job->remote)) {
                $stats['outside_region']++;

                continue;
            }

            $existing = JobPosting::where('job_source_id', $source->id)->where('external_id', $job->externalId)->first()
                ?? JobPosting::where('content_hash', JobPosting::makeHash($job->url, $job->title, $job->company))->first();

            if ($existing) {
                $existing->update(['status' => 'active', 'last_seen_at' => now()]);
                $stats['updated']++;

                continue;
            }

            if ($this->isDuplicateFromAnotherSource($job)) {
                $stats['duplicated']++;

                continue;
            }

            if ($stats['created'] >= $maxNew) {
                continue; // o resto entra na próxima coleta
            }

            $posting = JobPosting::create([
                'job_source_id' => $source->id,
                'external_id' => Str::limit($job->externalId, 250, ''),
                'url' => Str::limit($job->url, 500, ''),
                'title' => Str::limit($job->title, 250, ''),
                'company' => $job->company ? Str::limit($job->company, 250, '') : null,
                'description' => $job->description !== '' ? $job->description : $job->title,
                'city' => $job->city,
                'state' => $job->state,
                'location_text' => $job->locationText ? Str::limit($job->locationText, 160, '') : null,
                'is_international' => $job->international,
                'salary_min' => $job->salaryMin ?: null,
                'salary_max' => $job->salaryMax ?: null,
                'source_category' => $job->category ? Str::limit($job->category, 120, '') : null,
                'published_at' => $job->publishedAt ?? now(),
                'last_seen_at' => now(),
                'status' => 'active',
                'content_hash' => JobPosting::makeHash($job->url, $job->title, $job->company),
                ...$this->classifier->classify($job),
            ]);

            StructureJobPosting::dispatch($posting)->onQueue('jobs');
            $stats['created']++;
        }

        $stats['closed'] = $this->closeStale($source);
        $source->update(['last_fetched_at' => now()]);

        return $stats;
    }

    /** A mesma vaga anunciada em dois agregadores: mesmo título, mesma empresa e mesma cidade. */
    private function isDuplicateFromAnotherSource(JobData $job): bool
    {
        if (! $job->company) {
            return false;
        }

        return JobPosting::active()
            ->whereRaw('LOWER(title) = ?', [mb_strtolower($job->title)])
            ->whereRaw('LOWER(company) = ?', [mb_strtolower($job->company)])
            ->where(fn ($q) => $job->city ? $q->where('city', $job->city) : $q->whereNull('city'))
            ->exists();
    }

    /**
     * Fecha vagas que não aparecem há alguns dias nessa fonte ou que são antigas demais.
     * (Agregadores não avisam quando uma vaga sai do ar; a ausência é o sinal.)
     */
    private function closeStale(JobSource $source): int
    {
        return JobPosting::where('job_source_id', $source->id)
            ->active()
            ->where(fn ($q) => $q
                ->where('last_seen_at', '<', now()->subDays((int) config('faro.jobs.close_after_days')))
                ->orWhere('published_at', '<', now()->subDays((int) config('faro.jobs.max_age_days'))))
            ->update(['status' => 'closed']);
    }
}
