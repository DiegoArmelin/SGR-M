<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Ocorrência técnica registrada em um rodeiro ou motor individual. */
class PendenciaEquipamento extends Model
{
    protected $fillable = [
        'rodeiro_id', 'motor_id', 'descricao', 'prioridade', 'situacao',
        'registrado_por', 'resolvida_em', 'observacoes_resolucao',
    ];

    protected function casts(): array
    {
        return ['resolvida_em' => 'datetime'];
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
