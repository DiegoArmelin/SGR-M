<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permite ao administrador cancelar uma pendência registrada por engano.
 *
 * A pendência cancelada não é apagada: permanece no histórico com data,
 * autor e justificativa, e deixa de participar do ranking e dos indicadores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendencias_equipamentos', function (Blueprint $table) {
            $table->enum('situacao', ['aberta', 'resolvida', 'cancelada'])->default('aberta')->change();
            $table->dateTime('cancelada_em')->nullable();
            $table->foreignId('cancelada_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('motivo_cancelamento', 500)->nullable();
        });
    }

    public function down(): void
    {
        // Pendências canceladas voltam a ser abertas antes de remover a situação.
        DB::table('pendencias_equipamentos')->where('situacao', 'cancelada')->update(['situacao' => 'aberta']);

        Schema::table('pendencias_equipamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelada_por');
            $table->dropColumn(['cancelada_em', 'motivo_cancelamento']);
            $table->enum('situacao', ['aberta', 'resolvida'])->default('aberta')->change();
        });
    }
};
