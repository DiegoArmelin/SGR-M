<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instalacoes_motores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('eixo_id')
                ->constrained('eixos')
                ->restrictOnDelete();

            $table->foreignId('motor_id')
                ->constrained('motores')
                ->restrictOnDelete();

            $table->date('data_instalacao');

            $table->date('data_retirada')->nullable();

            $table->string('motivo_retirada', 150)->nullable();

            $table->text('observacoes')->nullable();

            // Identifica o eixo com motor ativo.
            $table->unsignedBigInteger('eixo_ativo')
                ->nullable()
                ->storedAs(
                    'CASE WHEN data_retirada IS NULL THEN eixo_id ELSE NULL END'
                );

            // Identifica o motor com instalação ativa.
            $table->unsignedBigInteger('motor_ativo')
                ->nullable()
                ->storedAs(
                    'CASE WHEN data_retirada IS NULL THEN motor_id ELSE NULL END'
                );

            $table->timestamps();

            // Impede dois motores ativos no mesmo eixo.
            $table->unique(
                'eixo_ativo',
                'uk_inst_mot_eixo_ativo'
            );

            // Impede o mesmo motor ativo em dois eixos.
            $table->unique(
                'motor_ativo',
                'uk_inst_mot_motor_ativo'
            );

            // Índices para consultas históricas.
            $table->index(
                ['eixo_id', 'data_retirada'],
                'idx_inst_mot_eixo'
            );

            $table->index(
                ['motor_id', 'data_retirada'],
                'idx_inst_mot_motor'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instalacoes_motores');
    }
};
