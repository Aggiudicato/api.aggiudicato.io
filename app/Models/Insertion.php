<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Insertion extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'publication_date' => 'date',
        ];
    }

    public function procedure(): HasOne
    {
        return $this->hasOne(Procedure::class);
    }

    public function lot(): HasOne
    {
        return $this->hasOne(Lot::class);
    }

    public function saleData(): HasOne
    {
        return $this->hasOne(SaleData::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
