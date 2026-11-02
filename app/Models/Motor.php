<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Motor extends Model
{
    protected $table = 'motores';

    protected $fillable = [
        'numero_serie',
        'status',
        'data_atualizacao_condicao',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_atualizacao_condicao' => 'datetime',
        ];
    }

    public function instalacoes(): HasMany
    {
        return $this->hasMany(InstalacaoMotor::class);
    }
}
