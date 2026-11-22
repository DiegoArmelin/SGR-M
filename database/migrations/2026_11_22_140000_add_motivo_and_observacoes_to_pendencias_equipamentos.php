<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relaciona a pendência a um motivo do catálogo já existente.
 *
 * Os novos campos são opcionais no esquema para preservar pendências
 * cadastradas antes desta evolução. Para novos cadastros, o controller
 * torna motivo_retirada_id obrigatório.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendencias_equipamentos', function (Blueprint $table) {
            $table->foreignId('motivo_retirada_id')
                ->nullable()
                ->constrained('motivos_retirada')
                ->restrictOnDelete();

            // Complementos sobre a ocorrência, independentes da resolução.
            $table->text('observacoes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pendencias_equipamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('motivo_retirada_id');
            $table->dropColumn('observacoes');
        });
    }
};
