<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instalacoes_rodeiros', function (Blueprint $table) {
            $table->id();

            $table->foreignId('eixo_id')
                ->constrained('eixos')
                ->restrictOnDelete();

            $table->foreignId('rodeiro_id')
                ->constrained('rodeiros')
                ->restrictOnDelete();

            $table->date('data_instalacao');

            $table->date('data_retirada')->nullable();

            $table->string('motivo_retirada', 150)->nullable();

            $table->text('observacoes')->nullable();

            // Retorna o eixo_id apenas quando a
            // instalação está ativa.
            $table->unsignedBigInteger('eixo_ativo')
                ->nullable()
                ->storedAs(
                    'IF(data_retirada IS NULL, eixo_id, NULL)'
                );

            // Retorna o rodeiro_id apenas quando a
            // instalação está ativa.
            $table->unsignedBigInteger('rodeiro_ativo')
                ->nullable()
                ->storedAs(
                    'IF(data_retirada IS NULL, rodeiro_id, NULL)'
                );

            $table->timestamps();

            // Impede dois rodeiros ativos no mesmo eixo.
            $table->unique(
                'eixo_ativo',
                'uk_inst_rod_eixo_ativo'
            );

            // Impede um rodeiro ativo em dois eixos.
            $table->unique(
                'rodeiro_ativo',
                'uk_inst_rod_rodeiro_ativo'
            );

            // Índices para consultar históricos.
            $table->index(
                ['eixo_id', 'data_retirada'],
                'idx_inst_rod_eixo'
            );

            $table->index(
                ['rodeiro_id', 'data_retirada'],
                'idx_inst_rod_rodeiro'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instalacoes_rodeiros');
    }
};
