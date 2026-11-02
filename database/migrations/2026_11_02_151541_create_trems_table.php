<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('frota_id')
                ->constrained('frotas')
                ->restrictOnDelete();

            $table->string('codigo', 20)->unique();
            $table->string('descricao', 150)->nullable();
            $table->boolean('ativo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trens');
    }
};