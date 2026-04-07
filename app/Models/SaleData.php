<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleData extends Model
{
    protected $table = 'sale_data';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sale_datetime' => 'datetime',
            'bid_deadline' => 'datetime',
            'prepaid_expense' => 'boolean',
            'contribution_not_due' => 'boolean',
        ];
    }

    public function insertion(): BelongsTo
    {
        return $this->belongsTo(Insertion::class);
    }
}
