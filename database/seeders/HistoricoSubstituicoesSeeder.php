<?php

namespace Database\Seeders;

use App\Models\Eixo;
use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\MotivoRetirada;
use App\Models\Rodeiro;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Dados SINTÉTICOS para testes acadêmicos de gráficos e relatórios.
 *
 * Insere 24 meses de histórico: 25 retiradas de rodeiros e 20 de motores
 * por mês (600 + 480). Nunca altera instalações ativas, pendências
 * existentes ou estado de equipamentos reais/de demonstrações anteriores.
 *
 * Cada retirada recebe número de série com prefixo HIST e marcador
 * identificador. Reexecução é idempotente; divergência aborta a transação.
 *
 * Atenção: registros históricos são SIMULAÇÕES estatísticas. Por não alterar
 * o parque já instalado, não devem ser tratados como linha do tempo real
 * de ocupação dos eixos (podem coincidir com instalações preexistentes).
 */
class HistoricoSubstituicoesSeeder extends Seeder
{
    private const FROTAS = ['I', 'J', 'L'];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Não execute este seeder acadêmico em produção.');
        }

        $motivos = MotivoRetirada::whereIn('codigo', [
            'ROD001','ROD002','ROD003','ROD004','ROD005','ROD006',
            'ROD007','ROD008','ROD009','MOT001','MOT002','MOT003',
            'MOT004','MOT005','MOT006',
        ])->get()->keyBy('codigo');

        foreach (['ROD004','ROD006','MOT006'] as $codigo) {
            if (!$motivos->has($codigo)) {
                throw new RuntimeException("Falta o motivo {$codigo}. Execute MotivosRetiradaSeeder antes.");
            }
        }

        // Seleciona apenas eixos de frotas de teste já cadastradas.
        $eixos = Eixo::with('carro.trem.frota')
            ->whereHas('carro.trem.frota', fn ($q) => $q->whereIn('codigo', self::FROTAS))
            ->orderBy('id')->get()->groupBy(
                fn ($eixo) => $eixo->carro->trem->frota->codigo
            );

        foreach (self::FROTAS as $frota) {
            if (!isset($eixos[$frota]) || $eixos[$frota]->count() < 30) {
                throw new RuntimeException("A frota {$frota} não possui eixos suficientes.");
            }
        }

        // Dez/2024 a Nov/2026 quando executado em nov/2026. Datas são
        // relativas ao mês de execução, garantindo últimos seis meses úteis.
        $inicio = now()->startOfMonth()->subMonths(23);

        DB::transaction(function () use ($eixos, $motivos, $inicio): void {
            for ($m = 0; $m < 24; $m++) {
                $mes = $inicio->copy()->addMonths($m);
                $limiteDia = $m === 23 ? min(20, now()->day) : 20;

                foreach (['rodeiro' => 25, 'motor' => 20] as $tipo => $quantidade) {
                    for ($n = 0; $n < $quantidade; $n++) {
                        // Rodízio da frota garante equilíbrio entre I/J/L.
                        $frota = self::FROTAS[($m + $n + ($tipo === 'motor' ? 1 : 0)) % 3];
                        $eixosFrota = $eixos[$frota]->values();

                        // Seleção determinística. Uma série única por evento
                        // impede duplicidade em reexecuções.
                        $indiceEixo = ($m * 37 + $n * 17 + ($tipo === 'motor' ? 11 : 0))
                            % $eixosFrota->count();
                        $eixo = $eixosFrota[$indiceEixo];
                        $dia = 1 + ($n * 7 + $m * 3) % $limiteDia;
                        $retirada = $mes->copy()->day($dia)->toDateString();
                        $instalacao = $mes->copy()->subMonth()->day(1)->toDateString();
                        $codigo = $this->codigoMotivo($tipo, $m, $n);
                        $motivo = $motivos->get($codigo);

                        if (!$motivo || $motivo->tipo_equipamento !== $tipo) {
                            throw new RuntimeException("Motivo {$codigo} incompatível com {$tipo}.");
                        }

                        $serie = sprintf('%s-HIST-%s-%02d',
                            $tipo === 'rodeiro' ? 'RD' : 'MT',
                            $mes->format('Ym'), $n + 1
                        );

                        $modelo = $tipo === 'rodeiro' ? Rodeiro::class : Motor::class;
                        $instalacaoModelo = $tipo === 'rodeiro'
                            ? InstalacaoRodeiro::class : InstalacaoMotor::class;
                        $fk = $tipo === 'rodeiro' ? 'rodeiro_id' : 'motor_id';
                        $equipamento = $modelo::firstOrCreate(
                            ['numero_serie' => $serie],
                            ['status' => 'inativo',
                             'observacoes' => 'REGISTRO HISTÓRICO FICTÍCIO — demonstração acadêmica']
                        );

                        if ($equipamento->status !== 'inativo') {
                            throw new RuntimeException("Equipamento histórico {$serie} foi alterado.");
                        }

                        $existente = $instalacaoModelo::where($fk, $equipamento->id)->first();
                        if ($existente) {
                            if ($existente->eixo_id !== $eixo->id
                                || $existente->data_retirada?->toDateString() !== $retirada
                                || $existente->motivo_retirada_id !== $motivo->id) {
                                throw new RuntimeException("Histórico divergente para {$serie}.");
                            }
                            continue;
                        }

                        $instalacaoModelo::create([
                            'eixo_id' => $eixo->id,
                            $fk => $equipamento->id,
                            'data_instalacao' => $instalacao,
                            'data_retirada' => $retirada,
                            'motivo_retirada' => $motivo->descricao,
                            'motivo_retirada_id' => $motivo->id,
                            'observacoes' => 'REGISTRO FICTÍCIO PARA RELATÓRIOS — não representa evento operacional',
                        ]);
                    }
                }
            }
        }, 3);

        $this->command?->info('Histórico sintético conferido: 600 rodeiros e 480 motores (24 meses).');
    }

    /** Frequência maior de periodicidade e diâmetro entre rodeiros. */
    private function codigoMotivo(string $tipo, int $mes, int $n): string
    {
        $percentil = ($n * 23 + $mes * 19) % 100;

        if ($tipo === 'rodeiro') {
            if ($percentil < 43) return 'ROD006'; // periodicidade vencida
            if ($percentil < 75) return 'ROD004'; // diâmetro condenado
            $outros = ['ROD001','ROD002','ROD003','ROD005','ROD007','ROD008','ROD009'];
            return $outros[($mes + $n) % count($outros)];
        }

        if ($percentil < 65) return 'MOT006'; // periodicidade vencida
        $outros = ['MOT001','MOT002','MOT003','MOT004','MOT005'];
        return $outros[($mes + $n) % count($outros)];
    }
}
