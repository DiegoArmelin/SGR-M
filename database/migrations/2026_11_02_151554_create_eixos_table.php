<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eixos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('carro_id')
                ->constrained('carros')
                ->restrictOnDelete();

            // Número da posição do eixo dentro do carro
            $table->unsignedTinyInteger('numero');

            $table->string('descricao', 150)->nullable();
            $table->boolean('ativo')->default(true);

            $table->timestamps();

            // Um carro não pode possuir duas posições
            // com o mesmo número de eixo
            $table->unique(['carro_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eixos');
    }
};
