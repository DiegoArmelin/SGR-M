<?php

namespace App\Http\Controllers;

use App\Models\Motor;
use App\Models\Rodeiro;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Consulta somente leitura de equipamentos; a autenticação é aplicada nas rotas.
class ConsultaEquipamentosController extends Controller
{
    /** Aplica os filtros e retorna a listagem paginada de rodeiros ou motores. */
    public function index(Request $request): View
    {
        // Restringe o tipo e a situação aos valores esperados pelo domínio.
        $filtros = $request->validate([
            'tipo' => ['nullable', 'in:rodeiro,motor'],
            'numero_serie' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:disponivel,instalado,em_manutencao,inativo'],
        ]);

        $tipo = $filtros['tipo'] ?? 'rodeiro';
        $numeroSerie = trim($filtros['numero_serie'] ?? '');
        $status = $filtros['status'] ?? '';

        // Seleciona o Model Eloquent adequado sem duplicar a lógica de filtros.
        $query = $tipo === 'motor' ? Motor::query() : Rodeiro::query();

        // Permite pesquisa por parte do número de série.
        if ($numeroSerie !== '') {
            $query->where('numero_serie', 'like', '%' . $numeroSerie . '%');
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        // Mantém filtros ao mudar de página e limita cada consulta a 20 itens.
        return view('equipamentos.index', [
            'equipamentos' => $query->orderBy('numero_serie')->paginate(20)->withQueryString(),
            'tipo' => $tipo,
            'numeroSerie' => $numeroSerie,
            'status' => $status,
        ]);
    }
}
