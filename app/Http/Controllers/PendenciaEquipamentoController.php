<?php

namespace App\Http\Controllers;

use App\Models\Motor;
use App\Models\PendenciaEquipamento;
use App\Models\Rodeiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Gerencia pendências técnicas, preservando o vínculo com o equipamento.
 * Todos os perfis autenticados podem registrar e consultar pendências.
 */
class PendenciaEquipamentoController extends Controller
{
    /** Lista pendências com filtros, ordenando abertas e mais prioritárias primeiro. */
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'situacao' => ['nullable', Rule::in(['aberta', 'resolvida'])],
            'tipo' => ['nullable', Rule::in(['rodeiro', 'motor'])],
        ]);

        $pendencias = PendenciaEquipamento::query()
            ->with(['rodeiro', 'motor', 'autor'])
            ->when($filtros['situacao'] ?? null, fn ($q, $v) => $q->where('situacao', $v))
            ->when(($filtros['tipo'] ?? '') === 'rodeiro', fn ($q) => $q->whereNotNull('rodeiro_id'))
            ->when(($filtros['tipo'] ?? '') === 'motor', fn ($q) => $q->whereNotNull('motor_id'))
            ->orderByRaw("CASE situacao WHEN 'aberta' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE prioridade WHEN 'critica' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return view('pendencias.index', compact('pendencias', 'filtros'));
    }

    /** Formulário para selecionar o tipo, a série e a prioridade da pendência. */
    public function create(Request $request): View
    {
        // A consulta de sugestões depende do tipo selecionado, evitando carregar
        // simultaneamente as milhares de séries dos dois tipos de equipamento.
        $filtros = $request->validate([
            'tipo' => ['nullable', Rule::in(['rodeiro', 'motor'])],
        ]);
        $tipo = $filtros['tipo'] ?? 'rodeiro';
        $modelo = $tipo === 'rodeiro' ? Rodeiro::class : Motor::class;

        return view('pendencias.create', [
            'tipo' => $tipo,
            'series' => $modelo::orderBy('numero_serie')->pluck('numero_serie'),
        ]);
    }

    /** Valida existência da série e grava a pendência vinculada ao equipamento. */
    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'tipo' => ['required', Rule::in(['rodeiro', 'motor'])],
            'numero_serie' => ['required', 'string', 'max:50'],
            'descricao' => ['required', 'string', 'max:500'],
            'prioridade' => ['required', Rule::in(['critica', 'alta', 'media', 'baixa'])],
        ]);

        $modelo = $dados['tipo'] === 'rodeiro' ? Rodeiro::class : Motor::class;
        $equipamento = $modelo::where('numero_serie', trim($dados['numero_serie']))->first();

        if (!$equipamento) {
            throw ValidationException::withMessages([
                'numero_serie' => 'Número de série não cadastrado para o tipo selecionado.',
            ]);
        }

        PendenciaEquipamento::create([
            'rodeiro_id' => $dados['tipo'] === 'rodeiro' ? $equipamento->id : null,
            'motor_id' => $dados['tipo'] === 'motor' ? $equipamento->id : null,
            'descricao' => $dados['descricao'],
            'prioridade' => $dados['prioridade'],
            'situacao' => 'aberta',
            'registrado_por' => $request->user()->id,
        ]);

        return redirect()->route('pendencias.index')
            ->with('success', 'Pendência registrada com sucesso.');
    }

    /** Encerra pendência aberta e registra quando e por que foi resolvida. */
    public function resolver(Request $request, PendenciaEquipamento $pendencia): RedirectResponse
    {
        $dados = $request->validate([
            'observacoes_resolucao' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($pendencia, $dados): void {
            $atual = PendenciaEquipamento::whereKey($pendencia->id)->lockForUpdate()->firstOrFail();
            if ($atual->situacao !== 'aberta') {
                throw ValidationException::withMessages([
                    'situacao' => 'Esta pendência já foi resolvida.',
                ]);
            }

            $atual->update([
                'situacao' => 'resolvida',
                'resolvida_em' => now(),
                'observacoes_resolucao' => $dados['observacoes_resolucao'],
            ]);
        });

        return redirect()->route('pendencias.index')
            ->with('success', 'Pendência resolvida com sucesso.');
    }
}
