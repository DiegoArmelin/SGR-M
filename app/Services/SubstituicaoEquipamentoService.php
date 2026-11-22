<?php

namespace App\Services;

use App\Models\Eixo;
use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\MotivoRetirada;
use App\Models\Rodeiro;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Executa a troca de um rodeiro ou motor em uma posição de eixo.
 *
 * Regra central: encerrar instalação atual, instalar peça em estoque
 * e alterar os dois status dentro de uma mesma transação.
 */
class SubstituicaoEquipamentoService
{
    public function substituir(
        Eixo $eixo,
        string $tipo,
        string $serieEntrada,
        string $serieSaida,
        string $data,
        int $motivoId,
        ?string $observacoes = null
    ): void {
        if (!in_array($tipo, ['rodeiro', 'motor'], true)) {
            throw new InvalidArgumentException('Tipo de equipamento desconhecido.');
        }

        DB::transaction(function () use (
            $eixo, $tipo, $serieEntrada, $serieSaida, $data, $motivoId, $observacoes
        ): void {
            // Bloqueia o eixo durante a operação. Assim duas trocas simultâneas
            // na mesma posição não devem processar o mesmo estado inicial.
            Eixo::query()->whereKey($eixo->id)->lockForUpdate()->firstOrFail();

            $isRodeiro = $tipo === 'rodeiro';
            $model = $isRodeiro ? Rodeiro::class : Motor::class;
            $instalacaoModel = $isRodeiro ? InstalacaoRodeiro::class : InstalacaoMotor::class;
            $fk = $isRodeiro ? 'rodeiro_id' : 'motor_id';

            // Identifica a instalação ainda aberta do eixo, sem apagar o histórico.
            $atual = $instalacaoModel::query()
                ->where('eixo_id', $eixo->id)
                ->whereNull('data_retirada')
                ->lockForUpdate()
                ->first();

            if (!$atual) {
                throw ValidationException::withMessages([
                    'eixo' => 'Este eixo não possui equipamento instalado desse tipo.',
                ]);
            }

            // Trava os equipamentos por ID em ordem estável para reduzir disputas.
            $equipamentoSaida = $model::query()->findOrFail($atual->{$fk});
            $equipamentoEntrada = $model::query()
                ->where('numero_serie', $serieEntrada)
                ->first();

            if (!$equipamentoEntrada) {
                throw ValidationException::withMessages([
                    'numero_serie_entrada' => 'Número de série não cadastrado.',
                ]);
            }

            $ids = [$equipamentoSaida->id, $equipamentoEntrada->id];
            sort($ids);
            $travados = $model::query()->whereIn('id', $ids)->orderBy('id')
                ->lockForUpdate()->get()->keyBy('id');
            $equipamentoSaida = $travados->get($equipamentoSaida->id);
            $equipamentoEntrada = $travados->get($equipamentoEntrada->id);

            // Número informado da peça removida atua como confirmação e proteção
            // contra formulários abertos antes de outra movimentação.
            if ($equipamentoSaida->numero_serie !== $serieSaida
                || $equipamentoSaida->status !== 'instalado') {
                throw ValidationException::withMessages([
                    'numero_serie_saida' => 'O equipamento instalado mudou. Atualize a página.',
                ]);
            }

            if ($equipamentoEntrada->id === $equipamentoSaida->id) {
                throw ValidationException::withMessages([
                    'numero_serie_entrada' => 'Escolha outro equipamento para substituição.',
                ]);
            }

            if ($equipamentoEntrada->status !== 'estoque') {
                throw ValidationException::withMessages([
                    'numero_serie_entrada' => 'O equipamento informado não está em estoque.',
                ]);
            }

            // Mesmo com status incorreto no cadastro, nunca instalar peça
            // que já possui vínculo ativo em qualquer eixo.
            if ($instalacaoModel::query()->where($fk, $equipamentoEntrada->id)
                ->whereNull('data_retirada')->exists()) {
                throw ValidationException::withMessages([
                    'numero_serie_entrada' => 'O equipamento já possui uma instalação ativa.',
                ]);
            }

            if ($data < $atual->data_instalacao->format('Y-m-d')) {
                throw ValidationException::withMessages([
                    'data_movimentacao' => 'A retirada não pode ser anterior à instalação.',
                ]);
            }

            // A consulta e o bloqueio ocorrem também no serviço, garantindo que
            // o tipo do motivo não possa ser adulterado na requisição HTTP.
            $motivo = MotivoRetirada::query()->whereKey($motivoId)
                ->where('tipo_equipamento', $tipo)
                ->where('ativo', true)->first();

            if (!$motivo) {
                throw ValidationException::withMessages([
                    'motivo_retirada_id' => 'Selecione um motivo ativo do tipo de equipamento correto.',
                ]);
            }

            // Finaliza o vínculo antigo, preservando-o para relatórios históricos.
            $atual->update([
                'data_retirada' => $data,
                'motivo_retirada' => $motivo->descricao, // Cópia textual preserva histórico legível.
                'motivo_retirada_id' => $motivo->id,
                'observacoes' => $observacoes,
            ]);

            // Cria o novo vínculo, que permanece ativo enquanto não há retirada.
            $instalacaoModel::create([
                'eixo_id' => $eixo->id,
                $fk => $equipamentoEntrada->id,
                'data_instalacao' => $data,
                'observacoes' => 'Instalação registrada por substituição no SGR-M.',
            ]);

            // Atualizações de status na mesma transação dos vínculos.
            $equipamentoSaida->update(['status' => 'em_manutencao']);
            $equipamentoEntrada->update(['status' => 'instalado']);
        }, 3);
    }
}
