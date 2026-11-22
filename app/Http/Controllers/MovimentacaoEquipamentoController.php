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
    public function create(Eixo $eixo, string $tipo): View
    {
        abort_unless(in_array($tipo, ['rodeiro', 'motor'], true), 404);

        // Carrega a hierarquia somente para identificar claramente a posição.
        $eixo->load('carro.trem.frota');
        $instalacao = $this->instalacaoAtual($eixo, $tipo);

        return view('equipamentos.movimentar', [
            'eixo' => $eixo,
            'tipo' => $tipo,
            'atual' => $instalacao?->{$tipo},
            'dataInstalacao' => $instalacao?->data_instalacao?->format('Y-m-d'),
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
            'numero_serie_entrada' => ['required', 'string', 'max:50'],
            'data_movimentacao' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'motivo_retirada_id' => ['required', 'integer', 'exists:motivos_retirada,id'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->substituir(
            $eixo,
            $tipo,
            $dados['numero_serie_entrada'],
            $dados['numero_serie_saida'],
            $dados['data_movimentacao'],
            (int) $dados['motivo_retirada_id'],
            $dados['observacoes'] ?? null
        );

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
