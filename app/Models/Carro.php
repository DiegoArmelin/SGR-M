<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carro extends Model
{
    protected $fillable = [
        'trem_id',
        'codigo',
        'posicao',
        'descricao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'posicao' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    public function trem(): BelongsTo
    {
        return $this->belongsTo(Trem::class);
    }

    public function eixos(): HasMany
    {
        return $this->hasMany(Eixo::class);
    }
}
