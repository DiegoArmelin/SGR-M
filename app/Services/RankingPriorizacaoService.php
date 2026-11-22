<?php

namespace App\Services;

use App\Models\Eixo;
use App\Models\Frota;
use App\Models\PendenciaEquipamento;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Consolida ocorrências abertas e condições dos equipamentos instalados.
 *
 * Regra de precedência: crítica (1), alta (2), média (3), baixa (4),
 * periodicidade >= 100% (5), menor diâmetro < 745 mm (6).
 *
 * A maior criticidade define a posição do item na lista; outras condições
 * ficam registradas como alertas adicionais, sem duplicar o equipamento.
 */
class RankingPriorizacaoService
{
    private const ORDEM_PENDENCIAS = [
        'critica' => 1, 'alta' => 2, 'media' => 3, 'baixa' => 4,
    ];

    /**
     * @return array{registros: LengthAwarePaginator, total: int, por_nivel: array}
     */
    public function consultar(?string $frotaCodigo, ?string $tipo, int $pagina = 1): array
    {
        // Somente posições ativas: a instalação encerrada não produz alertas.
        // Eager loading das duas classes evita consultas SQL repetidas por eixo.
        $eixos = Eixo::query()
            ->whereHas('carro.trem.frota', function ($query) use ($frotaCodigo) {
                if ($frotaCodigo) {
                    $query->where('codigo', $frotaCodigo);
                }
            })
            ->with([
                'carro.trem.frota',
                'instalacoesRodeiros' => fn ($q) => $q
                    ->whereNull('data_retirada')->with('rodeiro'),
                'instalacoesMotores' => fn ($q) => $q
                    ->whereNull('data_retirada')->with('motor'),
            ])
            ->get();

        $rodeiroIds = $eixos->flatMap(
            fn ($eixo) => $eixo->instalacoesRodeiros->pluck('rodeiro_id')
        )->unique()->values()->all();
        $motorIds = $eixos->flatMap(
            fn ($eixo) => $eixo->instalacoesMotores->pluck('motor_id')
        )->unique()->values()->all();

        // Pendências sem vínculo com a instalação atual não são priorizadas.
        $pendencias = PendenciaEquipamento::query()
            ->where('situacao', 'aberta')
            ->where(function ($q) use ($rodeiroIds, $motorIds) {
                $q->whereIn('rodeiro_id', $rodeiroIds)
                  ->orWhereIn('motor_id', $motorIds);
            })
            ->with('motivoRetirada')
            ->get()
            ->groupBy(fn ($p) => $p->eixo_id . ':'
                . ($p->rodeiro_id ? 'rodeiro:' . $p->rodeiro_id : 'motor:' . $p->motor_id));

        $itens = collect();

        foreach ($eixos as $eixo) {
            foreach (['rodeiro', 'motor'] as $classe) {
                if ($tipo && $tipo !== $classe) {
                    continue;
                }

                $instalacao = $classe === 'rodeiro'
                    ? $eixo->instalacoesRodeiros->first()
                    : $eixo->instalacoesMotores->first();
                $equipamento = $instalacao?->{$classe};

                if (!$equipamento) {
                    continue;
                }

                $vinculadas = $pendencias->get(
                    $eixo->id . ':' . $classe . ':' . $equipamento->id,
                    collect()
                );

                $nivel = 99;
                $alertas = [];

                foreach ($vinculadas as $pendencia) {
                    $nivel = min($nivel, self::ORDEM_PENDENCIAS[$pendencia->prioridade] ?? 99);
                    $alertas[] = 'Pendência ' . $pendencia->prioridade
                        . ': ' . ($pendencia->motivoRetirada?->descricao ?? $pendencia->descricao);
                }

                $periodicidade = null;
                $menorDiametro = null;

                if ($classe === 'rodeiro') {
                    $periodicidade = $equipamento->periodicidade !== null
                        ? (float) $equipamento->periodicidade : null;
                    $menorDiametro = $equipamento->menor_diametro;

                    // Condições não criam uma pendência formal; são alertas.
                    if ($periodicidade !== null && $periodicidade >= 100) {
                        $nivel = min($nivel, 5);
                        $alertas[] = 'Periodicidade atingiu ' . number_format($periodicidade, 2, ',', '.') . '%';
                    }

                    if ($menorDiametro !== null && $menorDiametro < 745) {
                        $nivel = min($nivel, 6);
                        $alertas[] = 'Menor diâmetro: ' . number_format($menorDiametro, 2, ',', '.') . ' mm';
                    }
                }

                if ($nivel === 99) {
                    continue; // Sem pendência ou alerta de condição.
                }

                $itens->push([
                    'nivel' => $nivel,
                    'frota' => $eixo->carro->trem->frota->codigo,
                    'trem' => $eixo->carro->trem->codigo,
                    'carro' => $eixo->carro->codigo,
                    'posicao' => $eixo->numero,
                    'eixo_id' => $eixo->id,
                    'tipo' => $classe,
                    'numero_serie' => $equipamento->numero_serie,
                    'periodicidade' => $periodicidade,
                    'menor_diametro' => $menorDiametro,
                    'pendencias' => $vinculadas->count(),
                    'alertas' => $alertas,
                ]);
            }
        }

        // Prioridade primeiro; entre rodeiros com a mesma prioridade,
        // maior periodicidade, menor diâmetro e localização estável.
        $ordenados = $itens->sort(function (array $a, array $b): int {
            return ($a['nivel'] <=> $b['nivel'])
                ?: (($b['periodicidade'] ?? -1) <=> ($a['periodicidade'] ?? -1))
                ?: (($a['menor_diametro'] ?? INF) <=> ($b['menor_diametro'] ?? INF))
                ?: strcmp(
                    $a['trem'] . $a['carro'] . str_pad((string) $a['posicao'], 2, '0', STR_PAD_LEFT) . $a['tipo'],
                    $b['trem'] . $b['carro'] . str_pad((string) $b['posicao'], 2, '0', STR_PAD_LEFT) . $b['tipo']
                );
        })->values();

        $porNivel = [];
        foreach (range(1, 6) as $nivel) {
            $porNivel[$nivel] = $ordenados->where('nivel', $nivel)->count();
        }

        $pagina = max(1, $pagina);
        $paginado = new LengthAwarePaginator(
            $ordenados->forPage($pagina, 30)->values(),
            $ordenados->count(),
            30,
            $pagina,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'registros' => $paginado,
            'total' => $ordenados->count(),
            'por_nivel' => $porNivel,
        ];
    }
}
