<?php

namespace App\Http\Controllers;

use App\Models\Trem;
use App\Models\Frota;
use App\Services\RankingPriorizacaoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Apresenta abas separadas de rodeiros/motores e três tabelas independentes.
 *
 * Cada tabela recebe filtros com prefixos próprios para não interferir nas
 * demais: p_ (pendências), d_ (diâmetros), r_ (periodicidade).
 */
class RankingPriorizacaoController extends Controller
{
    public function index(Request $request, RankingPriorizacaoService $ranking): View
    {
        $filtros = $request->validate([
            'tipo' => ['nullable', Rule::in(['rodeiro', 'motor'])],
            'p_frota' => ['nullable', 'string', 'exists:frotas,codigo'],
            'p_trem' => ['nullable', 'string', 'exists:trens,codigo'],
            'p_carro' => ['nullable', 'string', 'exists:carros,codigo'],
            'p_prioridade' => ['nullable', Rule::in(['critica', 'alta', 'media', 'baixa'])],
            'd_frota' => ['nullable', 'string', 'exists:frotas,codigo'],
            'd_trem' => ['nullable', 'string', 'exists:trens,codigo'],
            'd_carro' => ['nullable', 'string', 'exists:carros,codigo'],
            'r_frota' => ['nullable', 'string', 'exists:frotas,codigo'],
            'r_trem' => ['nullable', 'string', 'exists:trens,codigo'],
            'r_carro' => ['nullable', 'string', 'exists:carros,codigo'],
        ]);

        $tipo = $filtros['tipo'] ?? 'rodeiro';

        // Verifica também o pertencimento entre trem e carro para rejeitar
        // combinações adulteradas nos parâmetros GET.
        foreach (['p', 'd', 'r'] as $prefixo) {
            $codigoTrem = $filtros[$prefixo . '_trem'] ?? null;
            $codigoCarro = $filtros[$prefixo . '_carro'] ?? null;

            $codigoFrota = $filtros[$prefixo . '_frota'] ?? null;
            if ($codigoTrem && $codigoFrota) {
                abort_unless(
                    Trem::where('codigo', $codigoTrem)
                        ->whereHas('frota', fn ($q) => $q->where('codigo', $codigoFrota))
                        ->exists(), 404, 'O trem não pertence à frota selecionada.'
                );
            }
            if ($codigoCarro && $codigoFrota && !$codigoTrem) {
                abort_unless(
                    Trem::whereHas('frota', fn ($q) => $q->where('codigo', $codigoFrota))
                        ->whereHas('carros', fn ($q) => $q->where('codigo', $codigoCarro))
                        ->exists(), 404, 'O carro não pertence à frota.'
                );
            }
            if ($codigoCarro && $codigoTrem) {
                abort_unless(
                    Trem::where('codigo', $codigoTrem)
                        ->whereHas('carros', fn ($q) => $q->where('codigo', $codigoCarro))
                        ->exists(),
                    404,
                    'O carro não pertence ao trem selecionado.'
                );
            }
        }

        $resultado = $ranking->consultar($tipo, $filtros);

        return view('ranking.index', [
            'tipo' => $tipo,
            'filtros' => $filtros,
            'frotas' => Frota::orderBy('codigo')->get(),
            'trens' => Trem::with(['carros' => fn ($q) => $q->orderBy('posicao')])
                ->orderBy('codigo')->get(),
            'pendencias' => $resultado['pendencias'],
            'totalPendencias' => $resultado['totalPendencias'],
            'pendenciasPorPrioridade' => $resultado['pendenciasPorPrioridade'],
            'diametros' => $resultado['diametros'],
            'totalDiametros' => $resultado['totalDiametros'],
            'diametrosCriticos' => $resultado['diametrosCriticos'],
            'periodicidades' => $resultado['periodicidades'],
            'totalPeriodicidades' => $resultado['totalPeriodicidades'],
            'periodicidades105' => $resultado['periodicidades105'],
            'periodicidades110' => $resultado['periodicidades110'],
        ]);
    }
}
