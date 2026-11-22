<?php

namespace App\Http\Controllers;

use App\Models\Frota;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FrotaController extends Controller
{
    public function index(): View
    {
        return view('frotas.index', [
            'frotas' => Frota::withCount('trens')->orderBy('codigo')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('frotas.form', ['frota' => new Frota()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);
        $dados['ativa'] = $request->boolean('ativa');
        Frota::create($dados);

        return redirect()->route('frotas.index')->with('success', 'Frota cadastrada com sucesso.');
    }

    public function edit(Frota $frota): View
    {
        return view('frotas.form', compact('frota'));
    }

    public function update(Request $request, Frota $frota): RedirectResponse
    {
        $dados = $this->validar($request, $frota);
        $dados['ativa'] = $request->boolean('ativa');
        $frota->update($dados);

        return redirect()->route('frotas.index')->with('success', 'Frota atualizada com sucesso.');
    }

    public function destroy(Frota $frota): RedirectResponse
    {
        if ($frota->trens()->exists()) {
            return back()->withErrors(['exclusao' => 'Não é possível excluir uma frota que possui trens. Desative-a se necessário.']);
        }

        $frota->delete();

        return redirect()->route('frotas.index')->with('success', 'Frota excluída com sucesso.');
    }

    private function validar(Request $request, ?Frota $frota = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:10', Rule::unique('frotas', 'codigo')->ignore($frota?->id)],
            'descricao' => ['nullable', 'string', 'max:150'],
            'ativa' => ['nullable', 'boolean'],
        ]);
    }
}
