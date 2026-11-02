
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carros', function (Blueprint $table) {
            $table->id();

            // Trem ao qual o carro pertence
            $table->foreignId('trem_id')
                ->constrained('trens')
                ->restrictOnDelete();

            // Identificação do carro dentro da composição
            $table->string('codigo', 20);

            // Posição do carro na composição
            $table->unsignedTinyInteger('posicao');

            $table->string('descricao', 150)->nullable();
            $table->boolean('ativo')->default(true);

            $table->timestamps();

            // Não permite códigos repetidos no mesmo trem
            $table->unique(['trem_id', 'codigo']);

            // Não permite duas posições iguais no mesmo trem
            $table->unique(['trem_id', 'posicao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carros');
    }
};
