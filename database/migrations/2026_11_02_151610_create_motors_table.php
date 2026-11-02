<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motores', function (Blueprint $table) {
            $table->id();

            // Identificação única do motor
            $table->string('numero_serie', 50)->unique();

            // Situação cadastral do equipamento
            $table->enum('status', [
                'disponivel',
                'instalado',
                'em_manutencao',
                'inativo',
            ])->default('disponivel');

            // Data da última atualização de condição
            $table->timestamp('data_atualizacao_condicao')
                ->nullable();

            $table->text('observacoes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motores');
    }
};
