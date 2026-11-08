<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rodeiro extends Model
{
    protected $fillable = [
        'numero_serie',
        'diametro_lr',
        'diametro_lo',
        'periodicidade',
        'status',
        'data_atualizacao_condicao',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'diametro_lr' => 'decimal:2',
            'diametro_lo' => 'decimal:2',
            'periodicidade' => 'decimal:2',
            'data_atualizacao_condicao' => 'datetime',
        ];
    }

    public function getMenorDiametroAttribute(): ?float
    {
        $diametros = array_filter(
            [
                $this->diametro_lr,
                $this->diametro_lo,
            ],
            fn ($valor) => $valor !== null
        );

        if (empty($diametros)) {
            return null;
        }

        return (float) min($diametros);
    }
    public function instalacoes(): HasMany
    {
        return $this->hasMany(InstalacaoRodeiro::class);
    }
}
