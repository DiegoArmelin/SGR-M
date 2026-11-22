<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Consolida movimentações registradas nas tabelas de instalações.
 *
 * Filtros de data, frota e defeito aplicam-se igualmente às séries mensal,
 * aos motivos e à distribuição por frota. Gráficos usam dados agregados,
 * evitando consultar centenas de linhas individualmente na Blade.
 */
class HistoricoSubstituicoesService
{
    /** @return array<string,mixed> */
    public function consultar(?string $inicio, ?string $fim, ?string $frota, ?int $motivoId): array
    {
        $fimReferencia = $fim ? Carbon::parse($fim) : now();
        $mesInicial = $fimReferencia->copy()->startOfMonth()->subMonths(5);
        $mesFinal = $fimReferencia->copy()->endOfMonth();

        // Mantém seis categorias mensais inclusive quando não houve trocas.
        $meses = [];
        for ($i = 0; $i < 6; $i++) {
            $data = $mesInicial->copy()->addMonths($i);
            $chave = $data->format('Y-m');
            $meses[$chave] = ['chave' => $chave, 'rotulo' => $data->format('m/Y'), 'rodeiro' => 0, 'motor' => 0];
        }

        $motivos = [];
        $frotas = [];
        $totais = ['rodeiro' => 0, 'motor' => 0];

        foreach (['rodeiro', 'motor'] as $tipo) {
            $consulta = $this->base($tipo, $inicio, $fim, $frota, $motivoId);

            $totais[$tipo] = (clone $consulta)->count();

            $porMes = (clone $consulta)
                ->whereBetween('i.data_retirada', [
                    $mesInicial->toDateString(), $mesFinal->toDateString(),
                ])
                ->selectRaw('SUBSTR(i.data_retirada,1,7) as mes, COUNT(*) as total')
                ->groupByRaw('SUBSTR(i.data_retirada,1,7)')
                ->get();

            foreach ($porMes as $registro) {
                if (isset($meses[$registro->mes])) {
                    $meses[$registro->mes][$tipo] = (int) $registro->total;
                }
            }

            $porMotivo = (clone $consulta)
                ->leftJoin('motivos_retirada as m', 'm.id', '=', 'i.motivo_retirada_id')
                ->selectRaw("COALESCE(m.descricao, 'Não informado') as motivo, COUNT(*) as total")
                ->groupByRaw("COALESCE(m.descricao, 'Não informado')")
                ->get();

            foreach ($porMotivo as $linha) {
                $motivos[$linha->motivo] = ($motivos[$linha->motivo] ?? 0) + (int) $linha->total;
            }

            $porFrota = (clone $consulta)->select('f.codigo as frota')
                ->selectRaw('COUNT(*) as total')
                ->groupBy('f.codigo')->get();

            foreach ($porFrota as $linha) {
                $frotas[$linha->frota] ??= ['rodeiro' => 0, 'motor' => 0];
                $frotas[$linha->frota][$tipo] += (int) $linha->total;
            }
        }

        arsort($motivos);
        ksort($frotas);

        return [
            'meses' => array_values($meses),
            'motivos' => $motivos,
            'frotas' => $frotas,
            'totais' => $totais,
            'total' => array_sum($totais),
            'periodo_grafico' => [$mesInicial->toDateString(), $mesFinal->toDateString()],
        ];
    }

    /** Filtra somente retiradas efetivadas, respeitando o catálogo de motivos. */
    private function base(string $tipo, ?string $inicio, ?string $fim, ?string $frota, ?int $motivoId)
    {
        $tabela = $tipo === 'rodeiro' ? 'instalacoes_rodeiros' : 'instalacoes_motores';

        return DB::table($tabela . ' as i')
            ->join('eixos as e', 'e.id', '=', 'i.eixo_id')
            ->join('carros as c', 'c.id', '=', 'e.carro_id')
            ->join('trens as t', 't.id', '=', 'c.trem_id')
            ->join('frotas as f', 'f.id', '=', 't.frota_id')
            ->whereNotNull('i.data_retirada')
            ->when($inicio, fn ($q) => $q->whereDate('i.data_retirada', '>=', $inicio))
            ->when($fim, fn ($q) => $q->whereDate('i.data_retirada', '<=', $fim))
            ->when($frota, fn ($q) => $q->where('f.codigo', $frota))
            ->when($motivoId, fn ($q) => $q->where('i.motivo_retirada_id', $motivoId));
    }
}
