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

    public function motor(): BelongsTo
    {
        return $this->belongsTo(Motor::class);
    }
}
