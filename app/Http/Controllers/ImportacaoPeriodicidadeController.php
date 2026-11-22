<?php

namespace App\Http\Controllers;

use App\Services\ImportacaoPeriodicidadeCsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Interface administrativa de importação de periodicidade percentual. */
class ImportacaoPeriodicidadeController extends Controller
{
    /** Apresenta as instruções e o formulário de upload. */
    public function create(): View
    {
        return view('importacoes.periodicidade');
    }

    /** Valida o upload antes de processá-lo pelo serviço especializado. */
    public function store(
        Request $request,
        ImportacaoPeriodicidadeCsvService $importacao
    ): RedirectResponse {
        $dados = $request->validate([
            // Limite de 2 MB para arquivos .csv ou .txt (CSV de texto).
            'arquivo' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $resultado = $importacao->importar($dados['arquivo']);

        return redirect()->route('importacoes.periodicidade.create')
            ->with('success', "Importação concluída: {$resultado['atualizados']} rodeiro(s) atualizado(s).");
    }
}
