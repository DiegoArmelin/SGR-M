<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Substitui o status "disponivel" por "estoque" nos rodeiros e motores.
 *
 * MySQL armazena ENUM nativo; SQLite em testes utiliza uma restrição CHECK.
 * A migração deve alterar tanto os valores quanto as restrições aceitas.
 */
return new class extends Migration
{
    private array $tabelas = ['rodeiros', 'motores'];

    public function up(): void
    {
        foreach ($this->tabelas as $tabela) {
            if (DB::getDriverName() === 'mysql') {
                // Amplia temporariamente o ENUM para comportar os dois nomes.
                DB::statement("ALTER TABLE `{$tabela}` MODIFY COLUMN `status` ENUM('disponivel','estoque','instalado','em_manutencao','inativo') NOT NULL DEFAULT 'estoque'");
            } elseif (DB::getDriverName() === 'sqlite') {
                // Remove temporariamente o CHECK antigo, que não aceita 'estoque'.
                // Laravel reconstrói a definição da coluna com tipo texto.
                Schema::table($tabela, function (Blueprint $table): void {
                    $table->string('status')->default('estoque')->change();
                });
            }

            // Migra os valores sem perder os registros existentes.
            DB::table($tabela)->where('status', 'disponivel')->update([
                'status' => 'estoque',
            ]);

            if (DB::getDriverName() === 'mysql') {
                // Finaliza o ENUM MySQL retirando o nome antigo.
                DB::statement("ALTER TABLE `{$tabela}` MODIFY COLUMN `status` ENUM('estoque','instalado','em_manutencao','inativo') NOT NULL DEFAULT 'estoque'");
            } elseif (DB::getDriverName() === 'sqlite') {
                // Recria a restrição CHECK com os quatro status válidos.
                Schema::table($tabela, function (Blueprint $table): void {
                    $table->enum('status', [
                        'estoque', 'instalado', 'em_manutencao', 'inativo',
                    ])->default('estoque')->change();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tabelas as $tabela) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `{$tabela}` MODIFY COLUMN `status` ENUM('estoque','disponivel','instalado','em_manutencao','inativo') NOT NULL DEFAULT 'disponivel'");
            } elseif (DB::getDriverName() === 'sqlite') {
                // Durante o rollback, permite ambos os nomes antes de converter.
                Schema::table($tabela, function (Blueprint $table): void {
                    $table->string('status')->default('disponivel')->change();
                });
            }

            DB::table($tabela)->where('status', 'estoque')->update([
                'status' => 'disponivel',
            ]);

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `{$tabela}` MODIFY COLUMN `status` ENUM('disponivel','instalado','em_manutencao','inativo') NOT NULL DEFAULT 'disponivel'");
            } elseif (DB::getDriverName() === 'sqlite') {
                // Restaura os valores permitidos pelo esquema original.
                Schema::table($tabela, function (Blueprint $table): void {
                    $table->enum('status', [
                        'disponivel', 'instalado', 'em_manutencao', 'inativo',
                    ])->default('disponivel')->change();
                });
            }
        }
    }
};
