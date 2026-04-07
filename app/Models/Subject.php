<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subject extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'handles_sale' => 'boolean',
            'handles_viewing' => 'boolean',
        ];
    }

    public function procedure(): BelongsTo
    {
        return $this->belongsTo(Procedure::class);
    }
}
