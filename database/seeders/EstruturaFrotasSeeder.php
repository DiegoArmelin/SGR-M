<?php

namespace Database\Seeders;

use App\Models\Carro;
use App\Models\Eixo;
use App\Models\Frota;
use App\Models\Trem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cadastra a estrutura ferroviária fictícia do SGR-M.
 *
 * Uso: php artisan db:seed --class=EstruturaFrotasSeeder
 *
 * O comando é idempotente: execuções repetidas não criam duplicidades.
 * Registros existentes são preservados; divergências são reportadas como erro.
 * Não cadastra rodeiros, motores ou instalações.
 */
class EstruturaFrotasSeeder extends Seeder
{
    public function run(): void
    {
        // Este conjunto de dados destina-se exclusivamente ao projeto acadêmico.
        if (app()->environment('production')) {
            throw new RuntimeException('Não execute EstruturaFrotasSeeder em produção.');
        }

        // Intervalos inclusivos: I01..I25, J26..J51 e L26..L47.
        $configuracoes = [
            ['frota' => 'I', 'inicio' => 1, 'fim' => 25],
            ['frota' => 'J', 'inicio' => 26, 'fim' => 51],
            ['frota' => 'L', 'inicio' => 26, 'fim' => 47],
        ];

        // Uma transação evita importar apenas parte da estrutura em caso de falha.
        DB::transaction(function () use ($configuracoes): void {
            foreach ($configuracoes as $config) {
                $codigoFrota = $config['frota'];

                // Cria a frota apenas quando ainda não existe.
                // Uma frota já cadastrada não tem seus atributos sobrescritos.
                $frota = Frota::firstOrCreate(
                    ['codigo' => $codigoFrota],
                    ['descricao' => 'Frota fictícia ' . $codigoFrota, 'ativa' => true]
                );

                for ($numeroTrem = $config['inicio']; $numeroTrem <= $config['fim']; $numeroTrem++) {
                    // Ex.: frota I + 01 => I01; frota J + 26 => J26.
                    $codigoTrem = $codigoFrota . str_pad((string) $numeroTrem, 2, '0', STR_PAD_LEFT);

                    // O código do trem é globalmente único no banco.
                    $trem = Trem::firstOrCreate(
                        ['codigo' => $codigoTrem],
                        ['frota_id' => $frota->id, 'ativo' => true]
                    );

                    // Não altera silenciosamente um trem vinculado a outra frota.
                    if ($trem->frota_id !== $frota->id) {
                        throw new RuntimeException("Trem {$codigoTrem} já pertence a outra frota.");
                    }

                    // Cada trem tem seis carros, identificados pelo código do trem + posição.
                    for ($posicao = 1; $posicao <= 6; $posicao++) {
                        // Ex.: I01 + 1 => I011, I01 + 6 => I016.
                        $codigoCarro = $codigoTrem . $posicao;

                        // Verifica se a posição já foi atribuída a outro carro.
                        $carro = Carro::firstOrCreate(
                            ['trem_id' => $trem->id, 'posicao' => $posicao],
                            ['codigo' => $codigoCarro, 'ativo' => true]
                        );

                        // Evita substituir a identidade de um carro preexistente.
                        if ($carro->codigo !== $codigoCarro) {
                            throw new RuntimeException(
                                "Posição {$posicao} do trem {$codigoTrem} pertence ao carro {$carro->codigo}, esperado {$codigoCarro}."
                            );
                        }

                        // Cada carro possui exatamente quatro posições de eixo (1..4).
                        for ($numeroEixo = 1; $numeroEixo <= 4; $numeroEixo++) {
                            Eixo::firstOrCreate(
                                ['carro_id' => $carro->id, 'numero' => $numeroEixo],
                                ['descricao' => 'Eixo ' . $numeroEixo, 'ativo' => true]
                            );
                        }
                    }
                }
            }
        });

        $this->command?->info(
            'Estrutura fictícia conferida: 3 frotas, 73 trens, 438 carros e 1752 eixos previstos.'
        );
        $this->command?->warn(
            'Os totais acima correspondem à estrutura planejada; registros preexistentes fora do escopo não são excluídos.'
        );
    }
}
