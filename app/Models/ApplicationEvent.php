<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['from_status', 'to_status'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
