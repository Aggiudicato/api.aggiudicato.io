<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    protected $guarded = [];

    public function insertion(): BelongsTo
    {
        return $this->belongsTo(Insertion::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
