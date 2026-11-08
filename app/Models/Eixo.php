<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eixo extends Model
{
    protected $fillable = [
        'carro_id',
        'numero',
        'descricao',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'ativo' => 'boolean',
        ];
    }

    public function carro(): BelongsTo
    {
        return $this->belongsTo(Carro::class);
    }

    public function instalacoesRodeiros(): HasMany
    {
        return $this->hasMany(InstalacaoRodeiro::class);
    }

    public function instalacoesMotores(): HasMany
    {
        return $this->hasMany(InstalacaoMotor::class);
    }
}
