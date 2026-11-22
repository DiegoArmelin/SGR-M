<?php

namespace Database\Seeders;

use App\Models\MotivoRetirada;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Carga de demonstração (não operacional):
 * 70 pendências abertas de rodeiros: 5 críticas, 10 altas, 40 médias, 15 baixas.
 * 30 pendências abertas de motores: 2 críticas, 4 altas, 17 médias, 7 baixas.
 *
 * Adiciona 20 encerramentos históricos simulados (12 nos últimos 30 dias
 * e 8 nos 30 dias anteriores) APENAS para testar cards e tendência, sem
 * fechar pendências reais nem alterar os equipamentos instalados.
 */
class PendenciasDemonstracaoSeeder extends Seeder
{
    private const MARCADOR = '[SGRM-DEMO-PEND]';
    private const FROTAS = ['I', 'J', 'L'];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Carga acadêmica de pendências proibida em produção.');
        }

        $autor = User::query()->where('perfil', 'administrador')->first()
            ?? User::query()->first();
        if (!$autor) {
            throw new RuntimeException('Cadastre pelo menos um usuário antes de executar o seeder.');
        }

        $catalogo = MotivoRetirada::whereIn('codigo', [
            'ROD001','ROD002','ROD004','ROD006','ROD008',
            'MOT001','MOT002','MOT003','MOT006',
        ])->get()->keyBy('codigo');

        foreach (['ROD001','ROD004','ROD006','MOT001','MOT006'] as $codigo) {
            if (!$catalogo->has($codigo)) {
                throw new RuntimeException("Motivo {$codigo} ausente. Execute MotivosRetiradaSeeder.");
            }
        }

        // Só usa séries que realmente continuam instaladas. Cada pendência
        // recebe eixo_id e FK do equipamento, igual ao registro manual.
        $posicoes = [];
        foreach (['rodeiro' => 'instalacoes_rodeiros', 'motor' => 'instalacoes_motores'] as $tipo => $tabela) {
            $fk = $tipo === 'rodeiro' ? 'rodeiro_id' : 'motor_id';
            $posicoes[$tipo] = DB::table($tabela.' as i')
                ->join('eixos as e', 'e.id', '=', 'i.eixo_id')
                ->join('carros as c', 'c.id', '=', 'e.carro_id')
                ->join('trens as t', 't.id', '=', 'c.trem_id')
                ->join('frotas as f', 'f.id', '=', 't.frota_id')
                ->whereNull('i.data_retirada')
                ->whereIn('f.codigo', self::FROTAS)
                ->select('f.codigo as frota', 'e.id as eixo_id', 'i.'.$fk.' as equipamento_id')
                ->orderBy('e.id')->get()->groupBy('frota');
        }

        foreach (['rodeiro' => 35, 'motor' => 20] as $tipo => $minimo) {
            foreach (self::FROTAS as $frota) {
                if (($posicoes[$tipo]->get($frota)?->count() ?? 0) < $minimo) {
                    throw new RuntimeException("Poucas instalações ativas de {$tipo} na frota {$frota}.");
                }
            }
        }

        DB::transaction(function () use ($autor, $catalogo, $posicoes): void {
            $atuais = DB::table('pendencias_equipamentos')
                ->where('descricao', 'like', self::MARCADOR.'%')->count();

            if ($atuais > 0) {
                if ($atuais !== 120) {
                    throw new RuntimeException(
                        "Há {$atuais} pendências DEMO existentes; esperado 120. "
                        . 'Carga parcial/alterada: não sobrescrever registros automaticamente.'
                    );
                }
                $this->command?->info('As 120 pendências DEMO já existem; nenhuma duplicação.');
                return;
            }

            $agora = now();
            foreach ([
                'rodeiro' => ['critica'=>5,'alta'=>10,'media'=>40,'baixa'=>15],
                'motor' => ['critica'=>2,'alta'=>4,'media'=>17,'baixa'=>7],
            ] as $tipo => $distribuicao) {
                $prioridades = [];
                foreach ($distribuicao as $prioridade => $n) {
                    array_push($prioridades, ...array_fill(0, $n, $prioridade));
                }

                // Intercala criticidades para não concentrar uma categoria
                // inteira em apenas uma frota.
                $ordenadas = [];
                $restantes = $distribuicao;
                while (array_sum($restantes) > 0) {
                    foreach (array_keys($distribuicao) as $prioridade) {
                        if ($restantes[$prioridade] > 0) {
                            $ordenadas[] = $prioridade;
                            $restantes[$prioridade]--;
                        }
                    }
                }

                foreach ($ordenadas as $indice => $prioridade) {
                    $frota = self::FROTAS[$indice % 3];
                    $lista = $posicoes[$tipo]->get($frota)->values();
                    $indiceFrota = intdiv($indice, 3);
                    $alvo = $lista[$indiceFrota];
                    $codigo = $this->motivoPorTipo($tipo, $indice, $prioridade);
                    $motivo = $catalogo->get($codigo);
                    if (!$motivo) {
                        throw new RuntimeException("Motivo {$codigo} ausente no catálogo.");
                    }

                    // Datas sintéticas em ambas as janelas para exercitar
                    // o indicador de tendência. Nenhum encerramento real.
                    $criada = $indice % 5 === 0
                        ? $agora->copy()->subDays(35 + ($indice % 20))
                        : $agora->copy()->subDays(1 + ($indice % 26));

                    DB::table('pendencias_equipamentos')->insert([
                        'eixo_id' => $alvo->eixo_id,
                        'rodeiro_id' => $tipo === 'rodeiro' ? $alvo->equipamento_id : null,
                        'motor_id' => $tipo === 'motor' ? $alvo->equipamento_id : null,
                        'motivo_retirada_id' => $motivo->id,
                        'descricao' => self::MARCADOR . " Pendência fictícia de {$tipo} {$indice}",
                        'observacoes' => 'Carga acadêmica para testes de filtros e dashboard.',
                        'prioridade' => $prioridade,
                        'situacao' => 'aberta',
                        'registrado_por' => $autor->id,
                        'created_at' => $criada,
                        'updated_at' => $criada,
                    ]);
                }
            }

            // Pendências resolvidas FICTÍCIAS adicionais: card de 30 dias
            // não deve depender de haver intervenções manuais de teste.
            for ($indice = 0; $indice < 20; $indice++) {
                $tipo = $indice % 2 === 0 ? 'rodeiro' : 'motor';
                $frota = self::FROTAS[$indice % 3];
                $lista = $posicoes[$tipo]->get($frota)->values();
                $alvo = $lista[30 + intdiv($indice, 3)];
                $dias = $indice < 12 ? (2 + $indice * 2) : (34 + ($indice - 12) * 3);
                $encerrada = $agora->copy()->subDays($dias);
                $criada = $encerrada->copy()->subDays(10);
                $motivo = $catalogo->get($tipo === 'rodeiro' ? 'ROD006' : 'MOT006');

                DB::table('pendencias_equipamentos')->insert([
                    'eixo_id' => $alvo->eixo_id,
                    'rodeiro_id' => $tipo === 'rodeiro' ? $alvo->equipamento_id : null,
                    'motor_id' => $tipo === 'motor' ? $alvo->equipamento_id : null,
                    'motivo_retirada_id' => $motivo->id,
                    'descricao' => self::MARCADOR . " Encerramento fictício {$indice}",
                    'observacoes' => 'Exemplo acadêmico; nenhum equipamento foi substituído.',
                    'prioridade' => 'media',
                    'situacao' => 'resolvida',
                    'registrado_por' => $autor->id,
                    'resolvida_em' => $encerrada,
                    'observacoes_resolucao' => 'Resolução simulada para indicadores históricos.',
                    'created_at' => $criada,
                    'updated_at' => $encerrada,
                ]);
            }
        });

        $this->command?->info('Pendências fictícias: 70 rodeiros + 30 motores abertas; 20 encerradas adicionais (12 recentes).');
    }

    private function motivoPorTipo(string $tipo, int $indice, string $prioridade): string
    {
        if ($tipo === 'rodeiro') {
            if ($prioridade === 'critica') return 'ROD001';
            return ['ROD006','ROD004','ROD002','ROD008'][$indice % 4];
        }
        if ($prioridade === 'critica') return 'MOT001';
        return ['MOT006','MOT002','MOT003'][$indice % 3];
    }
}
