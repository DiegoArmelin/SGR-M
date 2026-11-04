<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rodeiros', function (Blueprint $table) {
            $table->id();

            // Identificação individual do equipamento
            $table->string('numero_serie', 50)->unique();

            // Diâmetros das rodas, em milímetros
            $table->decimal('diametro_lr', 6, 2)->nullable();
            $table->decimal('diametro_lo', 6, 2)->nullable();

            // Periodicidade recebida em porcentagem
            // Exemplo: 95.50 representa 95,5%
            $table->decimal('periodicidade', 6, 2)->nullable();

            // Situação cadastral do equipamento
            $table->enum('status', [
                'disponivel',
                'instalado',
                'em_manutencao',
                'inativo',
            ])->default('disponivel');

            // Data da última atualização das condições
            $table->timestamp('data_atualizacao_condicao')
                ->nullable();

            $table->text('observacoes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rodeiros');
    }
};
