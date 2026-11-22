<?php

namespace App\Http\Controllers;

use App\Models\Frota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Controlador MVC responsável pelo gerenciamento de frotas.
// A autorização de escrita é aplicada nas rotas por middleware de perfil.
class FrotaController extends Controller
{
    /** Lista as frotas e conta os trens vinculados sem carregar todos os registros. */
    public function index(): View
    {
        return view('frotas.index', [
            'frotas' => Frota::withCount('trens')->orderBy('codigo')->paginate(15),
        ]);
    }

    /** Exibe o formulário de uma nova frota (ainda não persistida). */
    public function create(): View
    {
        return view('frotas.form', ['frota' => new Frota()]);
    }

    /** Valida a requisição e grava uma nova frota no banco de dados. */
    public function store(Request $request): RedirectResponse
    {
        // Centraliza as regras de validação para criação e atualização.
        $dados = $this->validar($request);
        $dados['ativa'] = $request->boolean('ativa');
        Frota::create($dados);

        return redirect()->route('frotas.index')->with('success', 'Frota cadastrada com sucesso.');
    }

    /** Exibe uma frota existente usando Route Model Binding do Laravel. */
    public function edit(Frota $frota): View
    {
        return view('frotas.form', compact('frota'));
    }

    /** Atualiza os atributos permitidos, respeitando a unicidade do código. */
    public function update(Request $request, Frota $frota): RedirectResponse
    {
        $dados = $this->validar($request, $frota);
        $dados['ativa'] = $request->boolean('ativa');
        $frota->update($dados);

        return redirect()->route('frotas.index')->with('success', 'Frota atualizada com sucesso.');
    }

    /** Impede exclusão com dependências para preservar os trens vinculados. */
    public function destroy(Frota $frota): RedirectResponse
    {
        // Evita perda de integridade referencial; a alternativa é desativar a frota.
        if ($frota->trens()->exists()) {
            return back()->withErrors(['exclusao' => 'Não é possível excluir uma frota que possui trens. Desative-a se necessário.']);
        }

        $frota->delete();

        return redirect()->route('frotas.index')->with('success', 'Frota excluída com sucesso.');
    }

    /** Aplica as regras de entrada e ignora o próprio ID ao editar o código. */
    private function validar(Request $request, ?Frota $frota = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:10', Rule::unique('frotas', 'codigo')->ignore($frota?->id)],
            'descricao' => ['nullable', 'string', 'max:150'],
            'ativa' => ['nullable', 'boolean'],
        ]);
    }
}
