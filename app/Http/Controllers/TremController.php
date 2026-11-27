<?php

namespace App\Http\Controllers;

use App\Models\Frota;
use App\Models\Trem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

// Controlador MVC de trens; cada trem pertence a uma única frota.
class TremController extends Controller
{
    /** Lista os trens com filtros opcionais e carrega a frota sem consultas N+1. */
    public function index(Request $request): View
    {
        // Valida filtros para impedir valores inesperados na consulta.
        $filtros = $request->validate([
            'frota_id' => ['nullable', 'integer', 'exists:frotas,id'],
            'codigo' => ['nullable', 'string', 'max:20'],
        ]);

        // Eager loading da frota e contagem dos carros, com paginação de 15 registros.
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

    /** Exibe o formulário de cadastro com apenas frotas ativas. */
    public function create(): View
    {
        return view('trens.form', [
            'trem' => new Trem(),
            'frotas' => Frota::where('ativa', true)->orderBy('codigo')->get(),
        ]);
    }

    /** Valida os dados e impede a associação inicial a uma frota inativa. */
    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);
        $dados['ativo'] = $request->boolean('ativo');

        // Regra de negócio: novos trens só podem ser vinculados a frotas ativas.
        if (!Frota::whereKey($dados['frota_id'])->where('ativa', true)->exists()) {
            return back()->withInput()->withErrors(['frota_id' => 'Selecione uma frota ativa.']);
        }

        Trem::create($dados);

        return redirect()->route('trens.index')->with('success', 'Trem cadastrado com sucesso.');
    }

    /** Exibe o formulário e preserva a frota atual na lista, mesmo se inativa. */
    public function edit(Trem $trem): View
    {
        return view('trens.form', [
            'trem' => $trem,
            // orWhereKey() não existe no Eloquent Builder; orWhere pela chave mantém a frota atual.
            'frotas' => Frota::where('ativa', true)->orWhere('id', $trem->frota_id)->orderBy('codigo')->get(),
        ]);
    }

    /** Atualiza o trem e valida a situação da frota quando há troca de vínculo. */
    public function update(Request $request, Trem $trem): RedirectResponse
    {
        $dados = $this->validar($request, $trem);
        $dados['ativo'] = $request->boolean('ativo');

        // Mantém vínculo legado, mas bloqueia transferência para outra frota inativa.
        if ($trem->frota_id != $dados['frota_id'] && !Frota::whereKey($dados['frota_id'])->where('ativa', true)->exists()) {
            return back()->withInput()->withErrors(['frota_id' => 'Selecione uma frota ativa.']);
        }

        $trem->update($dados);

        return redirect()->route('trens.index')->with('success', 'Trem atualizado com sucesso.');
    }

    /** Exclui apenas trens sem carros vinculados, preservando a hierarquia. */
    public function destroy(Trem $trem): RedirectResponse
    {
        // Evita que um trem excluído deixe carros sem referência válida.
        if ($trem->carros()->exists()) {
            return back()->withErrors(['exclusao' => 'Não é possível excluir um trem que possui carros. Desative-o se necessário.']);
        }

        $trem->delete();

        return redirect()->route('trens.index')->with('success', 'Trem excluído com sucesso.');
    }

    /** Valida o vínculo com a frota e a unicidade global do código do trem. */
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
