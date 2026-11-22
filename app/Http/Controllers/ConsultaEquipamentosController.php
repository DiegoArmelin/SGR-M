<?php

namespace App\Http\Controllers;

use App\Models\Motor;
use App\Models\Rodeiro;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsultaEquipamentosController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'tipo' => ['nullable', 'in:rodeiro,motor'],
            'numero_serie' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'in:disponivel,instalado,em_manutencao,inativo'],
        ]);

        $tipo = $filtros['tipo'] ?? 'rodeiro';
        $numeroSerie = trim($filtros['numero_serie'] ?? '');
        $status = $filtros['status'] ?? '';

        $query = $tipo === 'motor' ? Motor::query() : Rodeiro::query();

        if ($numeroSerie !== '') {
            $query->where('numero_serie', 'like', '%' . $numeroSerie . '%');
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        return view('equipamentos.index', [
            'equipamentos' => $query->orderBy('numero_serie')->paginate(20)->withQueryString(),
            'tipo' => $tipo,
            'numeroSerie' => $numeroSerie,
            'status' => $status,
        ]);
    }
}
