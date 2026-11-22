<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalacaoRodeiro extends Model
{
    protected $table = 'instalacoes_rodeiros';

    protected $fillable = [
        'eixo_id',
        'rodeiro_id',
        'data_instalacao',
        'data_retirada',
        'motivo_retirada',
        'motivo_retirada_id',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_instalacao' => 'date',
            'data_retirada' => 'date',
        ];
    }

    /** Motivo categorizado da retirada, quando existente. */
    public function motivoRetirada(): BelongsTo
    {
        return $this->belongsTo(MotivoRetirada::class, 'motivo_retirada_id');
    }

    public function eixo(): BelongsTo
    {
        return $this->belongsTo(Eixo::class);
    }

    public function rodeiro(): BelongsTo
    {
        return $this->belongsTo(Rodeiro::class);
    }
}
