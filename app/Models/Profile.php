<?php

namespace App\Models;

use App\Enums\EducationLevel;
use App\Enums\JobLevel;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'headline', 'summary', 'desired_role', 'desired_level', 'area',
        'desired_modalities', 'desired_contracts', 'salary_expectation', 'availability', 'open_to_work', 'accepts_international', 'embedding', 'embedding_hash', 'matches_computed_at', 'accepted_levels', 'matches_hidden', 'last_digest_at',
        'city', 'state', 'phone', 'linkedin_url', 'portfolio_url',
        'willing_to_relocate', 'willing_to_travel', 'driver_license',
        'education_level', 'current_semester', 'graduation_year',
        'english_level', 'languages', 'digest_enabled',
    ];

    protected $hidden = ['embedding'];

    protected function casts(): array
    {
        return [
            'desired_level' => JobLevel::class,
            'education_level' => EducationLevel::class,
            'desired_modalities' => 'array',
            'desired_contracts' => 'array',
            'languages' => 'array',
            'driver_license' => 'array',
            'digest_enabled' => 'boolean',
            'open_to_work' => 'boolean',
            'accepts_international' => 'boolean',
            'willing_to_relocate' => 'boolean',
            'willing_to_travel' => 'boolean',
            'is_pcd' => 'boolean',
            'pcd_consent_at' => 'datetime',
            'embedding' => 'array',
            'matches_computed_at' => 'datetime',
            'accepted_levels' => 'array',
            'last_digest_at' => 'datetime',
            'embedded_at' => 'datetime',
        ];
    }

    /**
     * Percentual de preenchimento do perfil (o que recrutadores mais olham).
     *
     * @return array{percent: int, missing: list<string>}
     */
    public function completeness(): array
    {
        $checks = [
            __('Cargo desejado') => filled($this->desired_role),
            __('Área') => filled($this->area),
            __('Nível') => filled($this->desired_level),
            __('Cidade') => filled($this->city),
            __('Escolaridade') => filled($this->education_level),
            __('Experiências ou formações') => $this->experiences()->exists() || $this->educations()->exists(),
            __('Habilidades') => $this->skills()->exists(),
            __('Resumo') => filled($this->summary),
            __('Contato (WhatsApp ou LinkedIn)') => filled($this->phone) || filled($this->linkedin_url),
        ];

        $done = count(array_filter($checks));

        return [
            'percent' => (int) round($done / count($checks) * 100),
            'missing' => array_keys(array_filter($checks, fn ($ok) => ! $ok)),
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)
            ->withPivot(['proficiency', 'source'])
            ->withTimestamps();
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class)->orderByDesc('is_current')->orderByDesc('start_date');
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class)->orderByDesc('end_year')->orderByDesc('start_year');
    }
}
