<?php

namespace App\Http\Controllers;

use App\Models\Frota;
use App\Services\DashboardGerencialService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Indicadores consolidados somente leitura para os três perfis do projeto. */
class DashboardGerencialController extends Controller
{
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
}
