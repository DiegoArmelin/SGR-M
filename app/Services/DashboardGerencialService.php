<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores de leitura: instalações ativas, pendências, condições e trocas.
 * A consulta de movimentações utiliza data_retirada, e não created_at.
 * Nunca altera a situação de equipamentos ou pendências.
 */
class DashboardGerencialService
{
    /** @return array<string, mixed> */
    public function consultar(?string $frota, ?string $inicio, ?string $fim): array
    {
        $instalados = [];
        $periodicidade = [];

        foreach (['rodeiro', 'motor'] as $tipo) {
            $base = $this->instalacoesAtivas($tipo, $frota);
            $instalados[$tipo] = (clone $base)->count();
            $periodicidade[$tipo] = (clone $base)->where('eq.periodicidade', '>', 100)->count();
        }

        $rodeiros = $this->instalacoesAtivas('rodeiro', $frota);
        $menor = $this->menorDiametroSql();
        $diametros = [
            'atencao' => (clone $rodeiros)->whereRaw($menor . ' < ?', [745])->count(),
            'criticos' => (clone $rodeiros)->whereRaw($menor . ' <= ?', [742])->count(),
            'sem_leitura' => (clone $rodeiros)
                ->whereNull('eq.diametro_lr')->whereNull('eq.diametro_lo')->count(),
        ];

        // Contagem de ocorrências: um equipamento pode ter múltiplas pendências.
        // A ligação à instalação ATUAL evita considerar peças já substituídas.
        $pendencias = [];
        foreach (['rodeiro', 'motor'] as $tipo) {
            $q = $this->pendenciasAtivas($tipo, $frota);
            $pendencias[$tipo] = [
                'total' => (clone $q)->count(),
                'critica' => (clone $q)->where('p.prioridade', 'critica')->count(),
                'alta' => (clone $q)->where('p.prioridade', 'alta')->count(),
                'media' => (clone $q)->where('p.prioridade', 'media')->count(),
                'baixa' => (clone $q)->where('p.prioridade', 'baixa')->count(),
                'equipamentos' => (clone $q)->distinct()->count('i.id'),
            ];
        }

        $substituicoes = [];
        foreach (['rodeiro', 'motor'] as $tipo) {
            $q = $this->instalacoesHistoricas($tipo, $frota);
            if ($inicio) {
                $q->whereDate('i.data_retirada', '>=', $inicio);
            }
            if ($fim) {
                $q->whereDate('i.data_retirada', '<=', $fim);
            }
            $substituicoes[$tipo] = $q->count();
        }

        // Compara janelas corridas de 30 dias completos (inclusive hoje).
        // Aberturas seguem created_at; fechamentos seguem resolvida_em.
        // Não é possível inferir encerramento apenas por data_retirada.
        $hoje = now();
        $atualInicio = $hoje->copy()->startOfDay()->subDays(29);
        $anteriorInicio = $hoje->copy()->startOfDay()->subDays(59);
        $anteriorFim = $atualInicio->copy()->subSecond();

        $tendencia = [];
        foreach ([
            'atual' => [$atualInicio, $hoje],
            'anterior' => [$anteriorInicio, $anteriorFim],
        ] as $janela => [$inicioPeriodo, $fimPeriodo]) {
            // Pendências canceladas foram registros feitos por engano: não
            // contam como aberturas nem como encerramentos na tendência.
            $base = DB::table('pendencias_equipamentos as p')->where('p.situacao', '<>', 'cancelada');
            if ($frota) {
                $base->join('eixos as e', 'e.id', '=', 'p.eixo_id')
                    ->join('carros as c', 'c.id', '=', 'e.carro_id')
                    ->join('trens as t', 't.id', '=', 'c.trem_id')
                    ->join('frotas as f', 'f.id', '=', 't.frota_id')
                    ->where('f.codigo', $frota);
            }
            $tendencia[$janela] = [
                'abertas' => (clone $base)->whereBetween('p.created_at', [$inicioPeriodo, $fimPeriodo])->count(),
                'encerradas' => (clone $base)->where('p.situacao', 'resolvida')
                    ->whereBetween('p.resolvida_em', [$inicioPeriodo, $fimPeriodo])->count(),
            ];
            $tendencia[$janela]['saldo'] = $tendencia[$janela]['abertas'] - $tendencia[$janela]['encerradas'];
        }

        $encerradas30 = $tendencia['atual']['encerradas'];
        $variacaoSaldo = $tendencia['atual']['saldo'] - $tendencia['anterior']['saldo'];

        // Sinal positivo significa crescimento líquido do estoque de
        // pendências; negativo representa redução; zero, estabilidade.
        $tendencia['variacao_saldo'] = $variacaoSaldo;
        $tendencia['direcao'] = $variacaoSaldo > 0 ? 'aumentando'
            : ($variacaoSaldo < 0 ? 'diminuindo' : 'estavel');

        return compact(
            'instalados', 'periodicidade', 'diametros',
            'pendencias', 'substituicoes', 'encerradas30', 'tendencia'
        );
    }

    private function instalacoesAtivas(string $tipo, ?string $frota): Builder
    {
        return $this->baseInstalacoes($tipo, $frota)->whereNull('i.data_retirada');
    }

    private function instalacoesHistoricas(string $tipo, ?string $frota): Builder
    {
        return $this->baseInstalacoes($tipo, $frota)->whereNotNull('i.data_retirada');
    }

    /**
     * Reaproveita hierarquia para rodeiros e motores, impedindo contagem
     * por status textual sem instalação física correspondente.
     */
    private function baseInstalacoes(string $tipo, ?string $frota): Builder
    {
        $rodeiro = $tipo === 'rodeiro';
        $tabela = $rodeiro ? 'instalacoes_rodeiros' : 'instalacoes_motores';
        $equipamentos = $rodeiro ? 'rodeiros' : 'motores';
        $fk = $rodeiro ? 'rodeiro_id' : 'motor_id';

        return DB::table($tabela . ' as i')
            ->join($equipamentos . ' as eq', 'eq.id', '=', 'i.' . $fk)
            ->join('eixos as e', 'e.id', '=', 'i.eixo_id')
            ->join('carros as c', 'c.id', '=', 'e.carro_id')
            ->join('trens as t', 't.id', '=', 'c.trem_id')
            ->join('frotas as f', 'f.id', '=', 't.frota_id')
            ->when($frota, fn ($q) => $q->where('f.codigo', $frota));
    }

    private function pendenciasAtivas(string $tipo, ?string $frota): Builder
    {
        $fk = $tipo === 'rodeiro' ? 'rodeiro_id' : 'motor_id';

        return $this->instalacoesAtivas($tipo, $frota)
            ->join('pendencias_equipamentos as p', function ($j) use ($fk) {
                $j->on('p.eixo_id', '=', 'i.eixo_id')
                    ->on('p.' . $fk, '=', 'eq.id');
            })->where('p.situacao', 'aberta');
    }

    /** Mesma regra usada no ranking: considera menor leitura disponível. */
    private function menorDiametroSql(): string
    {
        return 'CASE WHEN eq.diametro_lr IS NULL THEN eq.diametro_lo '
            . 'WHEN eq.diametro_lo IS NULL THEN eq.diametro_lr '
            . 'WHEN eq.diametro_lr <= eq.diametro_lo THEN eq.diametro_lr '
            . 'ELSE eq.diametro_lo END';
    }
}
