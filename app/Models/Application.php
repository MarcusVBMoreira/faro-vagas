<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $fillable = ['job_posting_id', 'status', 'position', 'notes', 'applied_at'];

    protected $attributes = [
        'status' => 'saved',
        'position' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'applied_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Toda mudança de status vira um evento no histórico.
        static::created(function (Application $application) {
            $application->events()->create(['to_status' => $application->status->value]);
        });

        static::updated(function (Application $application) {
            if ($application->wasChanged('status')) {
                $original = $application->getOriginal('status');

                $application->events()->create([
                    'from_status' => $original instanceof ApplicationStatus ? $original->value : $original,
                    'to_status' => $application->status->value,
                ]);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ApplicationEvent::class);
    }
}
