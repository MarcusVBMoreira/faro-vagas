<?php

namespace App\Models;

use App\Enums\JobLevel;
use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'headline', 'desired_level', 'area', 'city', 'state', 'desired_modalities',
        'current_semester', 'graduation_year', 'english_level', 'summary', 'digest_enabled',
    ];

    protected $hidden = ['embedding'];

    protected function casts(): array
    {
        return [
            'desired_level' => JobLevel::class,
            'desired_modalities' => 'array',
            'digest_enabled' => 'boolean',
            'embedding' => 'array',
            'embedded_at' => 'datetime',
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
}
