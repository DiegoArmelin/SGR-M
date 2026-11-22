<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalacaoMotor extends Model
{
    protected $table = 'instalacoes_motores';

    protected $fillable = [
        'eixo_id',
        'motor_id',
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

    public function motor(): BelongsTo
    {
        return $this->belongsTo(Motor::class);
    }
}
