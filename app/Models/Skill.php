<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Skill extends Model
{
    protected $fillable = ['name', 'slug', 'category'];

    protected static function booted(): void
    {
        static::creating(function (Skill $skill) {
            $skill->slug ??= Str::slug($skill->name);
        });
    }

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class)->withPivot(['proficiency', 'source'])->withTimestamps();
    }

    public function jobPostings(): BelongsToMany
    {
        return $this->belongsToMany(JobPosting::class)->withPivot('is_required')->withTimestamps();
    }
}
