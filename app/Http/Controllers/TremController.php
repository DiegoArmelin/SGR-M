<?php

namespace App\Http\Controllers;

use App\Models\Frota;
use App\Models\Trem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TremController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'frota_id' => ['nullable', 'integer', 'exists:frotas,id'],
            'codigo' => ['nullable', 'string', 'max:20'],
        ]);

        $trens = Trem::with('frota')->withCount('carros')
            ->when($filtros['frota_id'] ?? null, fn ($q, $id) => $q->where('frota_id', $id))
            ->when(trim($filtros['codigo'] ?? '') !== '', fn ($q) => $q->where('codigo', 'like', '%' . trim($filtros['codigo']) . '%'))
            ->orderBy('codigo')->paginate(15)->withQueryString();

        return view('trens.index', [
            'trens' => $trens,
            'frotas' => Frota::orderBy('codigo')->get(),
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('trens.form', [
            'trem' => new Trem(),
            'frotas' => Frota::where('ativa', true)->orderBy('codigo')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);
        $dados['ativo'] = $request->boolean('ativo');

        if (!Frota::whereKey($dados['frota_id'])->where('ativa', true)->exists()) {
            return back()->withInput()->withErrors(['frota_id' => 'Selecione uma frota ativa.']);
        }

        Trem::create($dados);

        return redirect()->route('trens.index')->with('success', 'Trem cadastrado com sucesso.');
    }

    public function edit(Trem $trem): View
    {
        return view('trens.form', [
            'trem' => $trem,
            'frotas' => Frota::where('ativa', true)->orWhereKey($trem->frota_id)->orderBy('codigo')->get(),
        ]);
    }

    public function update(Request $request, Trem $trem): RedirectResponse
    {
        $dados = $this->validar($request, $trem);
        $dados['ativo'] = $request->boolean('ativo');

        if ($trem->frota_id != $dados['frota_id'] && !Frota::whereKey($dados['frota_id'])->where('ativa', true)->exists()) {
            return back()->withInput()->withErrors(['frota_id' => 'Selecione uma frota ativa.']);
        }

        $trem->update($dados);

        return redirect()->route('trens.index')->with('success', 'Trem atualizado com sucesso.');
    }

    public function destroy(Trem $trem): RedirectResponse
    {
        if ($trem->carros()->exists()) {
            return back()->withErrors(['exclusao' => 'Não é possível excluir um trem que possui carros. Desative-o se necessário.']);
        }

        $trem->delete();

        return redirect()->route('trens.index')->with('success', 'Trem excluído com sucesso.');
    }

    private function validar(Request $request, ?Trem $trem = null): array
    {
        return $request->validate([
            'frota_id' => ['required', 'integer', 'exists:frotas,id'],
            'codigo' => ['required', 'string', 'max:20', Rule::unique('trens', 'codigo')->ignore($trem?->id)],
            'descricao' => ['nullable', 'string', 'max:150'],
            'ativo' => ['nullable', 'boolean'],
        ]);
    }
}
