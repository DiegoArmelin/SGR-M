<?php

namespace App\Http\Controllers;

use App\Models\Carro;
use App\Models\Frota;
use App\Models\Trem;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consulta hierárquica, somente leitura, da estrutura ferroviária.
 *
 * Navegação: frota -> trem -> carro -> eixos -> equipamentos instalados.
 * As relações com instalação filtram data_retirada nula para mostrar apenas
 * equipamentos atualmente associados ao eixo.
 */
class ConsultaHierarquicaController extends Controller
{
    public function index(Request $request): View
    {
        // Todos os identificadores dos filtros devem existir no banco.
        $filtros = $request->validate([
            'frota_id' => ['nullable', 'integer', 'exists:frotas,id'],
            'trem_id' => ['nullable', 'integer', 'exists:trens,id'],
            'carro_id' => ['nullable', 'integer', 'exists:carros,id'],
        ]);

        $frotaId = $filtros['frota_id'] ?? null;
        $tremId = $filtros['trem_id'] ?? null;
        $carroId = $filtros['carro_id'] ?? null;

        // Filtros dependentes não podem ser utilizados sem seus respectivos pais.
        if (($tremId && !$frotaId) || ($carroId && !$tremId)) {
            abort(404, 'Seleção hierárquica incompleta.');
        }

        // A listagem inicial consulta apenas frotas; não carrega milhares de eixos.
        $frotas = Frota::orderBy('codigo')->get();
        $trens = $frotaId
            ? Trem::where('frota_id', $frotaId)->orderBy('codigo')->get()
            : collect();
        $carros = collect();
        $eixos = collect();

        $frotaSelecionada = $frotaId ? $frotas->firstWhere('id', $frotaId) : null;
        $tremSelecionado = null;
        $carroSelecionado = null;

        if ($tremId) {
            // Procurar dentro dos trens da frota selecionada impede combinações
            // inconsistentes, por exemplo frota I com um trem da frota J.
            $tremSelecionado = $trens->firstWhere('id', $tremId);

            if (!$tremSelecionado) {
                abort(404, 'Este trem não pertence à frota selecionada.');
            }

            $carros = Carro::where('trem_id', $tremId)->orderBy('posicao')->get();
        }

        if ($carroId) {
            $carroSelecionado = $carros->firstWhere('id', $carroId);

            if (!$carroSelecionado) {
                abort(404, 'Este carro não pertence ao trem selecionado.');
            }

            // Eager loading evita consultar o banco para cada eixo/equipamento.
            // Somente instalações ativas são carregadas; histórico fica preservado.
            $eixos = $carroSelecionado->eixos()
                ->with([
                    'instalacoesRodeiros' => fn ($q) => $q
                        ->whereNull('data_retirada')->with('rodeiro'),
                    'instalacoesMotores' => fn ($q) => $q
                        ->whereNull('data_retirada')->with('motor'),
                ])
                ->orderBy('numero')
                ->get();
        }

        return view('equipamentos.hierarquica', compact(
            'frotas', 'trens', 'carros', 'eixos',
            'frotaSelecionada', 'tremSelecionado', 'carroSelecionado',
            'frotaId', 'tremId', 'carroId'
        ));
    }
}
