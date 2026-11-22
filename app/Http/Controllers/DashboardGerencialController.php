<?php

namespace App\Http\Controllers;

use App\Models\Frota;
use App\Models\MotivoRetirada;
use App\Services\HistoricoSubstituicoesService;
use App\Services\DashboardGerencialService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Indicadores consolidados somente leitura para os três perfis do projeto. */
class DashboardGerencialController extends Controller
{
    /** Validação comum aos filtros dos gráficos e do PDF imprimível. */
    private function filtrosHistorico(Request $request): array
    {
        return $request->validate([
            'inicio' => ['nullable', 'date_format:Y-m-d'],
            'fim' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:inicio'],
            'frota' => ['nullable', 'string', Rule::exists('frotas', 'codigo')],
            'motivo_id' => ['nullable', 'integer', Rule::exists('motivos_retirada', 'id')],
        ]);
    }

    public function index(Request $request, DashboardGerencialService $dashboard): View
    {
        $filtros = $request->validate([
            'frota' => ['nullable', 'string', Rule::exists('frotas', 'codigo')],
            'inicio' => ['nullable', 'date_format:Y-m-d'],
            'fim' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:inicio'],
        ]);

        return view('dashboard-gerencial.index', [
            'dados' => $dashboard->consultar(
                $filtros['frota'] ?? null,
                $filtros['inicio'] ?? null,
                $filtros['fim'] ?? null
            ),
            'filtros' => $filtros,
            'frotas' => Frota::orderBy('codigo')->get(),
        ]);
    }
    /** Aba histórica: gráficos e filtros utilizam sempre a mesma agregação. */
    public function historico(Request $request, HistoricoSubstituicoesService $historico): View
    {
        $filtros = $this->filtrosHistorico($request);

        return view('dashboard-gerencial.historico', [
            'dados' => $historico->consultar(
                $filtros['inicio'] ?? null, $filtros['fim'] ?? null,
                $filtros['frota'] ?? null, $filtros['motivo_id'] ?? null
            ),
            'filtros' => $filtros,
            'frotas' => Frota::orderBy('codigo')->get(),
            'motivosCatalogo' => MotivoRetirada::orderBy('tipo_equipamento')->orderBy('descricao')->get(),
        ]);
    }

    /** Página preparada para Imprimir > Salvar como PDF pelo navegador. */
    public function imprimirHistorico(Request $request, HistoricoSubstituicoesService $historico): View
    {
        $filtros = $this->filtrosHistorico($request);

        return view('dashboard-gerencial.historico-pdf', [
            'dados' => $historico->consultar(
                $filtros['inicio'] ?? null, $filtros['fim'] ?? null,
                $filtros['frota'] ?? null, $filtros['motivo_id'] ?? null
            ),
            'filtros' => $filtros,
            'motivo' => isset($filtros['motivo_id'])
                ? MotivoRetirada::find($filtros['motivo_id'])?->descricao : null,
        ]);
    }
}
