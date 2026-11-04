<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trem extends Model
{
    // Define explicitamente a tabela utilizada pelo Model
    protected $table = 'trens';

    protected $fillable = [
        'frota_id',
        'codigo',
        'descricao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function frota(): BelongsTo
    {
        return $this->belongsTo(Frota::class);
    }

    public function carros(): HasMany
    {
        return $this->hasMany(Carro::class);
    }
}
