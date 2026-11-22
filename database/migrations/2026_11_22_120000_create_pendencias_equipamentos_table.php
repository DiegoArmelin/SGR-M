<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pendências técnicas ligadas a um rodeiro OU a um motor (sem apagar históricos). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendencias_equipamentos', function (Blueprint $table) {
            $table->id();
            // Campos opcionais permitem representar apenas um tipo por registro.
            $table->foreignId('rodeiro_id')->nullable()->constrained('rodeiros')->restrictOnDelete();
            $table->foreignId('motor_id')->nullable()->constrained('motores')->restrictOnDelete();
            $table->string('descricao', 500);
            $table->enum('prioridade', ['critica', 'alta', 'media', 'baixa']);
            $table->enum('situacao', ['aberta', 'resolvida'])->default('aberta');
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->dateTime('resolvida_em')->nullable();
            $table->text('observacoes_resolucao')->nullable();
            $table->timestamps();
            $table->index(['situacao', 'prioridade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendencias_equipamentos');
    }
};
