<?php

namespace Database\Seeders;

use App\Models\MotivoRetirada;
use Illuminate\Database\Seeder;

/**
 * Carrega os motivos informados na especificação acadêmica do SGR-M.
 *
 * Idempotente: identifica cada motivo por código estável.
 * Não modifica nem exclui motivos já existentes.
 */
class MotivosRetiradaSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            // Motivos exclusivos dos rodeiros (9 registros).
            ['ROD001', 'Defeito no rolamento do mancal', 'rodeiro'],
            ['ROD002', 'Defeito no rolomento da caixa redutora', 'rodeiro'],
            ['ROD003', 'Acoplamento resiliente danificado', 'rodeiro'],
            ['ROD004', 'Diâmetro condenado', 'rodeiro'],
            ['ROD005', 'troca programada', 'rodeiro'],
            ['ROD006', 'periodicidade vencida', 'rodeiro'],
            ['ROD007', 'vazamento excessivo de óleo', 'rodeiro'],
            ['ROD008', 'ruído no mancal', 'rodeiro'],
            ['ROD009', 'ruído no redutor', 'rodeiro'],

            // Motivos exclusivos dos motores (5 registros).
            ['MOT001', 'Motor desbalanceado', 'motor'],
            ['MOT002', 'Defeito no rolamento', 'motor'],
            ['MOT003', 'falha de isolamento', 'motor'],
            ['MOT004', 'sobreaquecimento', 'motor'],
            ['MOT005', 'ruído anormal', 'motor'],
        ];

        foreach ($motivos as [$codigo, $descricao, $tipo]) {
            // Evita duplicar registros quando o seeder for executado novamente.
            MotivoRetirada::firstOrCreate(
                ['codigo' => $codigo],
                ['descricao' => $descricao, 'tipo_equipamento' => $tipo, 'ativo' => true]
            );
        }

        $this->command?->info('Catálogo de motivos: 9 rodeiros + 5 motores.');
    }
}
