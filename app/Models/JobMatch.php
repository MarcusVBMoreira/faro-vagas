<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobMatch extends Model
{
    protected $fillable = [
        'job_posting_id', 'similarity', 'score', 'strengths', 'gaps', 'explanation',
        'ai_model', 'evaluated_at', 'feedback', 'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'similarity' => 'float',
            'score' => 'integer',
            'strengths' => 'array',
            'gaps' => 'array',
            'evaluated_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }
}
