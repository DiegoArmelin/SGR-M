<?php

namespace App\Http\Controllers;

use App\Services\ImportacaoPeriodicidadeCsvService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Interface administrativa de importação conjunta de condições dos rodeiros. */
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
            ->with('success', sprintf(
                'Importação processada: %d linha(s), %d atualizado(s), %d ignorado(s) por data e %d ignorado(s) por posição/série divergente.',
                $resultado['linhas'], $resultado['atualizados'],
                $resultado['ignorados_data'], $resultado['ignorados_posicao']
            ));
    }
}
