<?php

namespace App\Http\Controllers;

use App\Models\Eixo;
use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\MotivoRetirada;
use App\Models\Rodeiro;
use App\Services\SubstituicaoEquipamentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Apresenta a tela de substituição e recebe a confirmação da troca.
 * A lógica transacional fica concentrada no serviço de domínio.
 */
class MovimentacaoEquipamentoController extends Controller
{
    public function create(Request $request, Eixo $eixo, string $tipo): View
    {
        abort_unless(in_array($tipo, ['rodeiro', 'motor'], true), 404);

        // Carrega a hierarquia somente para identificar claramente a posição.
        $eixo->load('carro.trem.frota');
        $instalacao = $this->instalacaoAtual($eixo, $tipo);

        return view('equipamentos.movimentar', [
            'eixo' => $eixo,
            'tipo' => $tipo,
            // Permite retornar à lista de pendências após realizar a troca.
            'origemPendencias' => $request->query('origem') === 'pendencias',
            'atual' => $instalacao?->{$tipo},
            'dataInstalacao' => $instalacao?->data_instalacao?->format('Y-m-d'),
            // A lista contém somente itens do tipo solicitado com status em estoque.
            // A validação definitiva ocorre no serviço, durante a substituição.
            'equipamentosEstoque' => ($tipo === 'rodeiro' ? Rodeiro::query() : Motor::query())
                ->where('status', 'estoque')
                ->orderBy('numero_serie')
                ->pluck('numero_serie'),
            // Apenas motivos ativos do tipo de equipamento escolhido.
            'motivos' => MotivoRetirada::where('tipo_equipamento', $tipo)
                ->where('ativo', true)->orderBy('descricao')->get(),
        ]);
    }

    public function store(
        Request $request,
        Eixo $eixo,
        string $tipo,
        SubstituicaoEquipamentoService $service
    ): RedirectResponse {
        abort_unless(in_array($tipo, ['rodeiro', 'motor'], true), 404);

        // Validação de formato; regras de estoque e instalação são verificadas
        // novamente no serviço dentro da transação.
        $dados = $request->validate([
            'numero_serie_saida' => ['required', 'string', 'max:50'],
            // Permite escolher a série na lista ou digitá-la manualmente.
            // O campo manual prevalece se ambos estiverem preenchidos.
            'numero_serie_entrada' => ['nullable', 'string', 'max:50', 'required_without:numero_serie_estoque'],
            'numero_serie_estoque' => ['nullable', 'string', 'max:50', 'required_without:numero_serie_entrada'],
            'data_movimentacao' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'motivo_retirada_id' => ['required', 'integer', 'exists:motivos_retirada,id'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
            'origem' => ['nullable', 'in:pendencias'],
        ]);

        // Quando o usuário digita uma série, ela tem prioridade sobre a seleção.
        // trim() evita divergências ocasionadas por espaços antes/depois da série.
        $serieEntrada = trim($dados['numero_serie_entrada'] ?? '');
        if ($serieEntrada === '') {
            $serieEntrada = trim($dados['numero_serie_estoque'] ?? '');
        }

        $service->substituir(
            $eixo,
            $tipo,
            $serieEntrada,
            $dados['numero_serie_saida'],
            $dados['data_movimentacao'],
            (int) $dados['motivo_retirada_id'],
            $dados['observacoes'] ?? null
        );

        // Se a troca começou pela lista de pendências, retorna à listagem
        // para que o usuário veja imediatamente a pendência resolvida.
        if (($dados['origem'] ?? null) === 'pendencias') {
            return redirect()->route('pendencias.index')
                ->with('success', 'Substituição registrada; pendências vinculadas ao equipamento foram encerradas.');
        }

        return redirect()->route('equipamentos.hierarquica', [
            'frota_id' => $eixo->carro->trem->frota_id,
            'trem_id' => $eixo->carro->trem_id,
            'carro_id' => $eixo->carro_id,
        ])->with('success', 'Substituição registrada com sucesso.');
    }

    private function instalacaoAtual(Eixo $eixo, string $tipo): InstalacaoRodeiro|InstalacaoMotor|null
    {
        // Somente a instalação sem data de retirada representa o equipamento atual.
        if ($tipo === 'rodeiro') {
            return InstalacaoRodeiro::where('eixo_id', $eixo->id)
                ->whereNull('data_retirada')->with('rodeiro')->first();
        }

        return InstalacaoMotor::where('eixo_id', $eixo->id)
            ->whereNull('data_retirada')->with('motor')->first();
    }

    public function historico(Eixo $eixo): View
    {
        // Exibe entradas e saídas, inclusive a instalação que ainda está ativa.
        $eixo->load('carro.trem.frota');
        $rodeiros = InstalacaoRodeiro::where('eixo_id', $eixo->id)
            ->with('rodeiro')->orderByDesc('data_instalacao')->orderByDesc('id')->get();
        $motores = InstalacaoMotor::where('eixo_id', $eixo->id)
            ->with('motor')->orderByDesc('data_instalacao')->orderByDesc('id')->get();

        return view('equipamentos.historico', compact('eixo', 'rodeiros', 'motores'));
    }
}
