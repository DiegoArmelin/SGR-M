<?php

namespace Database\Seeders;

use App\Models\Eixo;
use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\Rodeiro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Cria equipamentos fictícios e suas instalações iniciais.
 *
 * Há um rodeiro e um motor por eixo das três frotas; outros 50 de
 * cada tipo ficam em estoque. Nenhum equipamento existente é apagado.
 *
 * Execução: php artisan db:seed --class=EquipamentosIniciaisSeeder
 */
class EquipamentosIniciaisSeeder extends Seeder
{
    private const SOBRESSALENTES = 50;
    private const DATA_INSTALACAO = '2026-01-01';

    public function run(): void
    {
        // Segurança: nunca carregar estes dados acadêmicos em produção.
        if (app()->environment('production')) {
            throw new RuntimeException('Seeder acadêmico não permitido em produção.');
        }

        DB::transaction(function (): void {
            // Busca somente os eixos pertencentes às frotas fictícias I, J e L.
            $eixos = Eixo::query()
                ->whereHas('carro.trem.frota', fn ($q) => $q->whereIn('codigo', ['I', 'J', 'L']))
                ->with('carro.trem.frota')
                ->get()
                ->sortBy(fn ($eixo) => sprintf(
                    '%s-%s-%02d-%02d',
                    $eixo->carro->trem->frota->codigo,
                    $eixo->carro->trem->codigo,
                    $eixo->carro->posicao,
                    $eixo->numero
                ))->values();

            // A carga completa só deve ocorrer depois da carga estrutural.
            if ($eixos->count() !== 1752) {
                throw new RuntimeException(
                    'Esperados 1.752 eixos nas frotas I/J/L; encontrados ' . $eixos->count() .
                    '. Execute EstruturaFrotasSeeder antes desta carga.'
                );
            }

            // Garante ausência de posições ocupadas com séries diferentes.
            // A transação reverte todas as alterações caso seja encontrada divergência.
            foreach ($eixos as $indice => $eixo) {
                $sequencia = $indice + 1;

                $rodeiro = Rodeiro::firstOrCreate(
                    ['numero_serie' => $this->serie('RD', $sequencia)],
                    ['status' => 'instalado', 'observacoes' => 'Equipamento fictício para demonstração acadêmica']
                );
                $motor = Motor::firstOrCreate(
                    ['numero_serie' => $this->serie('MT', $sequencia)],
                    ['status' => 'instalado', 'observacoes' => 'Equipamento fictício para demonstração acadêmica']
                );

                // Instalação aberta = data_retirada nula; os índices únicos
                // garantem um equipamento ativo por posição e vice-versa.
                $this->registrarRodeiro($eixo->id, $rodeiro);
                $this->registrarMotor($eixo->id, $motor);
            }

            // Gera 50 unidades adicionais de cada tipo, sem vínculo com eixos.
            for ($i = 1753; $i <= 1752 + self::SOBRESSALENTES; $i++) {
                $this->garantirEstoqueRodeiro($this->serie('RD', $i));
                $this->garantirEstoqueMotor($this->serie('MT', $i));
            }
        });

        $this->command?->info(
            'Carga fictícia verificada: 1.752 rodeiros e 1.752 motores instalados; ' .
            '50 rodeiros e 50 motores reservados em estoque.'
        );
    }

    /**
     * Gera números pseudoaleatórios, reproduzíveis e exclusivos por sequência.
     * O multiplicador 7919 é coprimo com 10 milhões, evitando colisões
     * no intervalo empregado. Não é um identificador operacional real.
     */
    private function serie(string $prefixo, int $indice): string
    {
        $numero = ($indice * 7919 + 104729) % 10000000;

        return $prefixo . '-SIM-' . str_pad((string) $numero, 7, '0', STR_PAD_LEFT);
    }

    private function registrarRodeiro(int $eixoId, Rodeiro $rodeiro): void
    {
        // Uma instalação existente pode ter sido retirada em testes manuais.
        // Não reabrimos instalações históricas nem reinstalamos peças retiradas.
        $ativaNoEixo = InstalacaoRodeiro::where('eixo_id', $eixoId)
            ->whereNull('data_retirada')->first();
        $ativaDoItem = InstalacaoRodeiro::where('rodeiro_id', $rodeiro->id)
            ->whereNull('data_retirada')->first();

        if ($ativaNoEixo || $ativaDoItem) {
            if ($ativaNoEixo?->rodeiro_id !== $rodeiro->id ||
                $ativaDoItem?->eixo_id !== $eixoId ||
                $rodeiro->status !== 'instalado') {
                throw new RuntimeException("Divergência na instalação do rodeiro {$rodeiro->numero_serie} (eixo {$eixoId}).");
            }
            return; // instalação já consistente: seeder idempotente.
        }

        if ($rodeiro->instalacoes()->exists() || $rodeiro->status !== 'instalado') {
            throw new RuntimeException("Rodeiro {$rodeiro->numero_serie} possui histórico ou status incompatível.");
        }

        InstalacaoRodeiro::create([
            'eixo_id' => $eixoId,
            'rodeiro_id' => $rodeiro->id,
            'data_instalacao' => self::DATA_INSTALACAO,
            'observacoes' => 'Instalação inicial fictícia (seeder acadêmico).',
        ]);
    }

    private function registrarMotor(int $eixoId, Motor $motor): void
    {
        $ativaNoEixo = InstalacaoMotor::where('eixo_id', $eixoId)
            ->whereNull('data_retirada')->first();
        $ativaDoItem = InstalacaoMotor::where('motor_id', $motor->id)
            ->whereNull('data_retirada')->first();

        if ($ativaNoEixo || $ativaDoItem) {
            if ($ativaNoEixo?->motor_id !== $motor->id ||
                $ativaDoItem?->eixo_id !== $eixoId ||
                $motor->status !== 'instalado') {
                throw new RuntimeException("Divergência na instalação do motor {$motor->numero_serie} (eixo {$eixoId}).");
            }
            return;
        }

        if ($motor->instalacoes()->exists() || $motor->status !== 'instalado') {
            throw new RuntimeException("Motor {$motor->numero_serie} possui histórico ou status incompatível.");
        }

        InstalacaoMotor::create([
            'eixo_id' => $eixoId,
            'motor_id' => $motor->id,
            'data_instalacao' => self::DATA_INSTALACAO,
            'observacoes' => 'Instalação inicial fictícia (seeder acadêmico).',
        ]);
    }

    private function garantirEstoqueRodeiro(string $serie): void
    {
        $rodeiro = Rodeiro::firstOrCreate(['numero_serie' => $serie], [
            'status' => 'estoque',
            'observacoes' => 'Sobressalente fictício para demonstração acadêmica',
        ]);

        if ($rodeiro->status !== 'estoque' || $rodeiro->instalacoes()->exists()) {
            throw new RuntimeException("Rodeiro sobressalente {$serie} apresenta vínculo ou status incompatível.");
        }
    }

    private function garantirEstoqueMotor(string $serie): void
    {
        $motor = Motor::firstOrCreate(['numero_serie' => $serie], [
            'status' => 'estoque',
            'observacoes' => 'Sobressalente fictício para demonstração acadêmica',
        ]);

        if ($motor->status !== 'estoque' || $motor->instalacoes()->exists()) {
            throw new RuntimeException("Motor sobressalente {$serie} apresenta vínculo ou status incompatível.");
        }
    }
}
