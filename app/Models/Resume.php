<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    protected $fillable = [
        'original_name', 'storage_path', 'mime_type', 'size_bytes', 'extracted_text', 'is_current', 'parsed_at',
    ];

    protected $hidden = ['extracted_text', 'storage_path'];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'parsed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
