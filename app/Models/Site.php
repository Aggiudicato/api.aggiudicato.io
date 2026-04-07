<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Site extends Model
{
    protected $guarded = [];

    public function insertion(): BelongsTo
    {
        return $this->belongsTo(Insertion::class);
    }
}
