<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'operation', 'model', 'input_tokens', 'output_tokens'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
