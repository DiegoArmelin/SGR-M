<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ocorrência técnica registrada em um rodeiro ou motor individual. */
class PendenciaEquipamento extends Model
{
    // Nome explícito: o plural automático do Eloquent não corresponde à migration.
    protected $table = 'pendencias_equipamentos';

    protected $fillable = [
        'eixo_id', 'rodeiro_id', 'motor_id', 'motivo_retirada_id', 'descricao',
        'observacoes', 'prioridade', 'situacao',
        'registrado_por', 'resolvida_em', 'observacoes_resolucao',
    ];

    protected function casts(): array
    {
        return ['resolvida_em' => 'datetime'];
    }

    /** Motivo do catálogo; registros antigos podem não possuir essa referência. */
    public function motivoRetirada(): BelongsTo
    {
        return $this->belongsTo(MotivoRetirada::class, 'motivo_retirada_id');
    }

    /** Posição original onde o problema foi registrado. */
    public function eixo(): BelongsTo
    {
        return $this->belongsTo(Eixo::class);
    }

    public function rodeiro(): BelongsTo
    {
        return $this->belongsTo(Rodeiro::class);
    }

    public function motor(): BelongsTo
    {
        return $this->belongsTo(Motor::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
