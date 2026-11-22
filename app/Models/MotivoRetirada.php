<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Catálogo único de motivos de retirada, separados por tipo de equipamento.
 *
 * 'ativo' define se o motivo pode ser selecionado em novas operações;
 * desativar um motivo não afeta os registros históricos que o utilizam.
 */
class MotivoRetirada extends Model
{
    protected $table = 'motivos_retirada';

    protected $fillable = [
        'codigo', 'descricao', 'tipo_equipamento', 'ativo',
    ];

    protected function casts(): array
    {
        return ['ativo' => 'boolean'];
    }
}
