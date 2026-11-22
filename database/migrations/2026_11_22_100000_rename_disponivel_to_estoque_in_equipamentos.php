<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Padroniza a nomenclatura do status de equipamento em estoque.
 *
 * Em MySQL a coluna é ENUM, portanto a alteração ocorre em duas etapas:
 * primeiro aceita 'estoque' e 'disponivel'; depois migra os valores e
 * retira a antiga opção. Em SQLite, o ENUM original é representado
 * por texto, sem necessidade de alterar o tipo da coluna.
 */
return new class extends Migration
{
    private array $tabelas = ['rodeiros', 'motores'];

    public function up(): void
    {
        foreach ($this->tabelas as $tabela) {
            if (DB::getDriverName() === 'mysql') {
                // Etapa intermediária garante que dados antigos possam ser convertidos.
                DB::statement("ALTER TABLE `{$tabela}` MODIFY COLUMN `status` ENUM('disponivel','estoque','instalado','em_manutencao','inativo') NOT NULL DEFAULT 'estoque'");
            }

            // Converte valores existentes antes de remover o status antigo.
            DB::table($tabela)->where('status', 'disponivel')->update(['status' => 'estoque']);

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `{$tabela}` MODIFY COLUMN `status` ENUM('estoque','instalado','em_manutencao','inativo') NOT NULL DEFAULT 'estoque'");
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tabelas as $tabela) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `{$tabela}` MODIFY COLUMN `status` ENUM('estoque','disponivel','instalado','em_manutencao','inativo') NOT NULL DEFAULT 'disponivel'");
            }

            DB::table($tabela)->where('status', 'estoque')->update(['status' => 'disponivel']);

            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `{$tabela}` MODIFY COLUMN `status` ENUM('disponivel','instalado','em_manutencao','inativo') NOT NULL DEFAULT 'disponivel'");
            }
        }
    }
};
