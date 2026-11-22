<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normaliza os motivos de retirada em um único catálogo por tipo de equipamento.
 *
 * A coluna textual antiga permanece como descrição histórica e compatibilidade
 * com movimentações já gravadas, evitando qualquer perda de dados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motivos_retirada', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('descricao', 150);
            $table->enum('tipo_equipamento', ['rodeiro', 'motor']);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            // Impede motivos duplicados dentro de um tipo.
            $table->unique(['tipo_equipamento', 'descricao'], 'uk_motivo_tipo_descricao');
        });

        foreach (['instalacoes_rodeiros', 'instalacoes_motores'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                // É opcional porque as instalações anteriores não têm esse ID.
                $table->foreignId('motivo_retirada_id')
                    ->nullable()
                    ->constrained('motivos_retirada')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['instalacoes_rodeiros', 'instalacoes_motores'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropConstrainedForeignId('motivo_retirada_id');
            });
        }

        Schema::dropIfExists('motivos_retirada');
    }
};
