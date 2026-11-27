<?php

namespace App\Http\Controllers;

use App\Models\Carro;
use App\Models\Eixo;
use App\Models\Frota;
use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\MotivoRetirada;
use App\Models\Trem;
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
            'situacao' => ['nullable', Rule::in(['aberta', 'resolvida', 'cancelada'])],
            'tipo' => ['nullable', Rule::in(['rodeiro', 'motor'])],
        ]);

        $pendencias = PendenciaEquipamento::query()
            ->with(['rodeiro', 'motor', 'motivoRetirada', 'autor', 'canceladaPor', 'eixo.carro.trem.frota',
                // Carrega o vínculo atual apenas para habilitar a ação de troca
                // quando a pendência ainda se refere ao item instalado.
                'eixo.instalacoesRodeiros' => fn ($q) => $q->whereNull('data_retirada'),
                'eixo.instalacoesMotores' => fn ($q) => $q->whereNull('data_retirada')])
            ->when($filtros['situacao'] ?? null, fn ($q, $v) => $q->where('situacao', $v))
            ->when(($filtros['tipo'] ?? '') === 'rodeiro', fn ($q) => $q->whereNotNull('rodeiro_id'))
            ->when(($filtros['tipo'] ?? '') === 'motor', fn ($q) => $q->whereNotNull('motor_id'))
            ->orderByRaw("CASE situacao WHEN 'aberta' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE prioridade WHEN 'critica' THEN 1 WHEN 'alta' THEN 2 WHEN 'media' THEN 3 ELSE 4 END")
            ->orderByDesc('created_at')
            ->paginate(20)->withQueryString();

        return view('pendencias.index', compact('pendencias', 'filtros'));
    }

    /**
     * Exibe seletores progressivos de frota, trem, carro e eixo.
     *
     * O formulário GET muda apenas os filtros; nenhum equipamento é alterado.
     * As opções são sempre conferidas contra seus pais para evitar combinações
     * inválidas de IDs (ex.: carro da frota J no trem I01).
     */
    public function create(Request $request): View
    {
        $filtros = $request->validate([
            'frota_id' => ['nullable', 'integer', 'exists:frotas,id'],
            'trem_id' => ['nullable', 'integer', 'exists:trens,id'],
            'carro_id' => ['nullable', 'integer', 'exists:carros,id'],
            'eixo_id' => ['nullable', 'integer', 'exists:eixos,id'],
            'tipo' => ['nullable', Rule::in(['rodeiro', 'motor'])],
        ]);

        $frotaId = $filtros['frota_id'] ?? null;
        $tremId = $filtros['trem_id'] ?? null;
        $carroId = $filtros['carro_id'] ?? null;
        $eixoId = $filtros['eixo_id'] ?? null;
        $tipo = $filtros['tipo'] ?? 'rodeiro';

        if (($tremId && !$frotaId) || ($carroId && !$tremId)
            || ($eixoId && !$carroId)) {
            abort(404, 'Seleção hierárquica incompleta.');
        }

        $frotas = Frota::orderBy('codigo')->get();
        $trens = $frotaId
            ? Trem::where('frota_id', $frotaId)->orderBy('codigo')->get()
            : collect();
        $carros = collect();
        $eixos = collect();
        $eixoSelecionado = null;
        $equipamentoInstalado = null;

        if ($tremId) {
            abort_unless($trens->contains('id', $tremId), 404);
            $carros = Carro::where('trem_id', $tremId)->orderBy('posicao')->get();
        }

        if ($carroId) {
            abort_unless($carros->contains('id', $carroId), 404);
            $eixos = Eixo::where('carro_id', $carroId)->orderBy('numero')->get();
        }

        if ($eixoId) {
            $eixoSelecionado = $eixos->firstWhere('id', $eixoId);
            abort_unless($eixoSelecionado, 404);

            // Consulta apenas a instalação atual, sem incluir equipamento retirado.
            $instalacaoModel = $tipo === 'rodeiro'
                ? InstalacaoRodeiro::class : InstalacaoMotor::class;
            $relacao = $tipo === 'rodeiro' ? 'rodeiro' : 'motor';

            $instalacao = $instalacaoModel::where('eixo_id', $eixoId)
                ->whereNull('data_retirada')->with($relacao)->first();
            $equipamentoInstalado = $instalacao?->{$relacao};
        }

        // Reutiliza o catálogo de defeitos e motivos, limitado ao tipo e a ativos.
        $motivos = MotivoRetirada::query()
            ->where('tipo_equipamento', $tipo)
            ->where('ativo', true)
            ->orderBy('descricao')
            ->get();

        return view('pendencias.create', compact(
            'frotas', 'trens', 'carros', 'eixos', 'eixoSelecionado',
            'equipamentoInstalado', 'frotaId', 'tremId', 'carroId', 'eixoId', 'tipo',
            'motivos'
        ));
    }

    /**
     * Identifica o equipamento pela posição escolhida no momento da gravação.
     *
     * IDs de frota, trem e carro são revalidados no servidor, não apenas na view.
     * A pendência registra tanto a posição de origem quanto o equipamento atual,
     * mantendo a rastreabilidade se o item for substituído futuramente.
     */
    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'frota_id' => ['required', 'integer', 'exists:frotas,id'],
            'trem_id' => ['required', 'integer', 'exists:trens,id'],
            'carro_id' => ['required', 'integer', 'exists:carros,id'],
            'eixo_id' => ['required', 'integer', 'exists:eixos,id'],
            'tipo' => ['required', Rule::in(['rodeiro', 'motor'])],
            'motivo_retirada_id' => ['required', 'integer', 'exists:motivos_retirada,id'],
            'descricao' => ['nullable', 'string', 'max:500'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'prioridade' => ['required', Rule::in(['critica', 'alta', 'media', 'baixa'])],
        ]);

        // O ID precisa pertencer ao catálogo ativo do mesmo tipo de equipamento.
        // Validar novamente no POST evita utilizar opções alteradas manualmente.
        $motivo = MotivoRetirada::query()
            ->whereKey($dados['motivo_retirada_id'])
            ->where('tipo_equipamento', $dados['tipo'])
            ->where('ativo', true)
            ->first();

        if (!$motivo) {
            throw ValidationException::withMessages([
                'motivo_retirada_id' => 'Selecione um motivo ativo do tipo de equipamento correto.',
            ]);
        }

        // Confere pertencimento dos quatro níveis, impedindo associações falsas.
        $eixo = Eixo::query()->whereKey($dados['eixo_id'])
            ->where('carro_id', $dados['carro_id'])
            ->whereHas('carro', fn ($q) => $q
                ->where('trem_id', $dados['trem_id'])
                ->whereHas('trem', fn ($t) => $t->where('frota_id', $dados['frota_id'])))
            ->first();

        if (!$eixo) {
            throw ValidationException::withMessages([
                'eixo_id' => 'A posição selecionada não pertence à frota, trem e carro informados.',
            ]);
        }

        // Lê a instalação sob bloqueio e grava a pendência na mesma transação.
        // Assim, uma movimentação concorrente não deve mudar o vínculo no
        // intervalo entre consulta e criação da pendência.
        DB::transaction(function () use ($dados, $eixo, $request, $motivo): void {
            Eixo::whereKey($eixo->id)->lockForUpdate()->firstOrFail();

            $tipo = $dados['tipo'];
            $model = $tipo === 'rodeiro'
                ? InstalacaoRodeiro::class : InstalacaoMotor::class;
            $fk = $tipo === 'rodeiro' ? 'rodeiro_id' : 'motor_id';

            $instalacao = $model::where('eixo_id', $eixo->id)
                ->whereNull('data_retirada')->lockForUpdate()->first();

            if (!$instalacao) {
                throw ValidationException::withMessages([
                    'eixo_id' => 'Não há equipamento deste tipo instalado nesta posição.',
                ]);
            }

            PendenciaEquipamento::create([
                'eixo_id' => $eixo->id,
                'rodeiro_id' => $tipo === 'rodeiro' ? $instalacao->{$fk} : null,
                'motor_id' => $tipo === 'motor' ? $instalacao->{$fk} : null,
                // O texto de descrição é mantido para compatibilidade com as
                // pendências antigas; novas ocorrências usam o motivo do catálogo.
                'motivo_retirada_id' => $motivo->id,
                'descricao' => $motivo->descricao,
                'observacoes' => $dados['observacoes'] ?? null,
                'prioridade' => $dados['prioridade'],
                'situacao' => 'aberta',
                'registrado_por' => $request->user()->id,
            ]);
        }, 3);

        return redirect()->route('pendencias.index')
            ->with('success', 'Pendência registrada com sucesso.');
    }

    /**
     * Cancela uma pendência aberta registrada por engano (somente administrador).
     *
     * O registro é preservado com data, autor e justificativa. A situação
     * "cancelada" fica fora do ranking e dos indicadores, que consideram
     * apenas pendências abertas.
     */
    public function cancelar(Request $request, PendenciaEquipamento $pendencia): RedirectResponse
    {
        $dados = $request->validate(
            ['motivo_cancelamento' => ['required', 'string', 'min:10', 'max:500']],
            [],
            ['motivo_cancelamento' => 'justificativa do cancelamento']
        );

        DB::transaction(function () use ($pendencia, $dados, $request): void {
            // Bloqueia a linha: uma substituição concorrente pode estar
            // encerrando a mesma pendência neste instante.
            $atual = PendenciaEquipamento::query()->whereKey($pendencia->id)->lockForUpdate()->firstOrFail();

            if ($atual->situacao !== 'aberta') {
                throw ValidationException::withMessages([
                    'motivo_cancelamento' => 'Somente pendências abertas podem ser canceladas.',
                ]);
            }

            $atual->update([
                'situacao' => 'cancelada',
                'cancelada_em' => now(),
                'cancelada_por' => $request->user()->id,
                'motivo_cancelamento' => trim($dados['motivo_cancelamento']),
            ]);
        });

        return back()->with('success',
            'Pendência cancelada. Ela permanece no histórico e deixou de contar no ranking e nos indicadores.');
    }
}
