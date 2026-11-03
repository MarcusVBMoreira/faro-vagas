<?php

namespace App\Models;

use App\Enums\JobSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobSource extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'config', 'is_active', 'last_fetched_at'];

    protected function casts(): array
    {
        return [
            'type' => JobSourceType::class,
            'config' => 'array',
            'is_active' => 'boolean',
            'last_fetched_at' => 'datetime',
        ];
    }

    public function jobPostings(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }
}
