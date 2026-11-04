<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Resume extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'original_name', 'storage_path', 'mime_type', 'size_bytes', 'extracted_text',
        'is_current', 'status', 'error_message', 'parsed_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'is_current' => true,
    ];

    protected $hidden = ['extracted_text', 'storage_path'];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'parsed_at' => 'datetime',
        ];
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_DONE, self::STATUS_FAILED], true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Na fila para análise',
            self::STATUS_PROCESSING => 'A IA está lendo seu currículo',
            self::STATUS_DONE => 'Analisado',
            self::STATUS_FAILED => 'Não foi possível analisar',
            default => $this->status,
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
