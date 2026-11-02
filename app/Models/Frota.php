<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Frota extends Model
{
    protected $fillable = [
        'codigo',
        'descricao',
        'ativa',
    ];

    protected function casts(): array
    {
        return [
            'ativa' => 'boolean',
        ];
    }

    public function trens(): HasMany
    {
        return $this->hasMany(Trem::class);
    }
}
