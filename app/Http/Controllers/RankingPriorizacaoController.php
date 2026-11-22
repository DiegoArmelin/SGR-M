<?php

namespace App\Http\Controllers;

use App\Models\Frota;
use App\Services\RankingPriorizacaoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Consulta somente leitura do ranking de equipamentos instalados. */
class RankingPriorizacaoController extends Controller
{
    public function index(Request $request, RankingPriorizacaoService $ranking): View
    {
        $filtros = $request->validate([
            'frota' => ['nullable', 'string', 'exists:frotas,codigo'],
            'tipo' => ['nullable', Rule::in(['rodeiro', 'motor'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $resultado = $ranking->consultar(
            $filtros['frota'] ?? null,
            $filtros['tipo'] ?? null,
            (int) ($filtros['page'] ?? 1)
        );

        return view('ranking.index', [
            'registros' => $resultado['registros'],
            'total' => $resultado['total'],
            'porNivel' => $resultado['por_nivel'],
            'frotas' => Frota::orderBy('codigo')->get(),
            'filtros' => $filtros,
        ]);
    }
}
