<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mantém a posição original da ocorrência, mesmo após a troca do equipamento.
 *
 * Nullable: pendências criadas antes da mudança não tinham eixo informado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendencias_equipamentos', function (Blueprint $table) {
            $table->foreignId('eixo_id')
                ->nullable()
                ->constrained('eixos')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pendencias_equipamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('eixo_id');
        });
    }
};
