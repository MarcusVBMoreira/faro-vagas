<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    protected $fillable = ['role', 'company', 'location', 'start_date', 'end_date', 'is_current', 'description', 'source'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function period(): string
    {
        $start = $this->start_date?->translatedFormat('M Y');
        $end = $this->is_current ? __('o momento') : $this->end_date?->translatedFormat('M Y');

        return trim(($start ?? '').($start || $end ? ' – ' : '').($end ?? ''), ' –');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
