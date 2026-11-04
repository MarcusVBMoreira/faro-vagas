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
            $skill->slug ??= self::slugFor($skill->name);
        });
    }

    /**
     * Slug estável para comparar habilidades. Trata símbolos que o Str::slug apagaria:
     * "C#" vira "c-sharp" e "C++" vira "c-plus-plus" (senão os dois virariam "c").
     */
    public static function slugFor(string $name): string
    {
        $name = str_replace(['#', '+'], [' sharp', ' plus'], $name);

        return Str::slug($name);
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
