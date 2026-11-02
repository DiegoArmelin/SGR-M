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
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_instalacao' => 'date',
            'data_retirada' => 'date',
        ];
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
