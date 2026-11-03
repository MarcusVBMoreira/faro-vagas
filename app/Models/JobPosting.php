<?php

namespace App\Models;

use App\Enums\JobLevel;
use App\Enums\WorkModality;
use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory;

    protected $fillable = [
        'job_source_id', 'external_id', 'url', 'title', 'company', 'description', 'level', 'modality',
        'city', 'state', 'contract_type', 'salary_min', 'salary_max', 'published_at', 'expires_at',
        'status', 'ai_summary', 'extracted_at', 'content_hash',
    ];

    protected $hidden = ['embedding'];

    protected function casts(): array
    {
        return [
            'level' => JobLevel::class,
            'modality' => WorkModality::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'extracted_at' => 'datetime',
            'embedding' => 'array',
        ];
    }

    /**
     * Hash usado para não salvar a mesma vaga duas vezes (mesma URL ou mesmo título + empresa).
     */
    public static function makeHash(string $url, string $title, ?string $company = null): string
    {
        return hash('sha256', mb_strtolower(trim($url)).'|'.mb_strtolower(trim($title)).'|'.mb_strtolower(trim((string) $company)));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(JobSource::class, 'job_source_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withPivot('is_required')->withTimestamps();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(JobMatch::class);
    }
}
