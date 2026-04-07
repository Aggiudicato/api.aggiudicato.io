<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'viewing_location' => 'array',
            'pickup_location' => 'array',
            'ateco_categories' => 'array',
            'complaint_date' => 'date',
        ];
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function cadastralRecords(): HasMany
    {
        return $this->hasMany(CadastralRecord::class);
    }
}
