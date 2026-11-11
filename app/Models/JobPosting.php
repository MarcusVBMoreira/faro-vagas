<?php

namespace App\Models;

use App\Enums\ContractType;
use App\Enums\EducationLevel;
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
        'job_source_id', 'user_id', 'external_id', 'url', 'title', 'company', 'description', 'level', 'area',
        'education_level', 'modality', 'city', 'state', 'location_text', 'contract_type', 'is_pcd', 'is_international',
        'salary_min', 'salary_max', 'published_at', 'expires_at', 'status', 'ai_summary', 'extracted_at',
        'content_hash', 'source_category', 'last_seen_at', 'embedding', 'embedded_at',
    ];

    protected $hidden = ['embedding'];

    protected function casts(): array
    {
        return [
            'level' => JobLevel::class,
            'education_level' => EducationLevel::class,
            'modality' => WorkModality::class,
            'is_pcd' => 'boolean',
            'is_international' => 'boolean',
            'last_seen_at' => 'datetime',
            'embedded_at' => 'datetime',
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

    /** Vagas públicas (coletadas) + as que a própria pessoa adicionou. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('user_id')->when($user, fn ($q) => $q->orWhere('user_id', $user->id)));
    }

    public function contractLabel(): ?string
    {
        return ContractType::tryFrom((string) $this->contract_type)?->label();
    }

    public function locationLabel(): ?string
    {
        if ($this->city && str_starts_with(mb_strtolower($this->city), 'estado d')) {
            return $this->state; // registros antigos em que o estado veio no lugar da cidade
        }
        if ($this->city || $this->state) {
            return collect([$this->city, $this->state])->filter()->implode(', ');
        }

        return $this->location_text;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
