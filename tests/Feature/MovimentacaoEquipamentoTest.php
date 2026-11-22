<?php

namespace Tests\Feature;

use App\Models\Carro;
use App\Models\Eixo;
use App\Models\Frota;
use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\Rodeiro;
use App\Models\Trem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimentacaoEquipamentoTest extends TestCase
{
    use RefreshDatabase;

    /** Monta uma posição fictícia com um rodeiro e motor já instalados. */
    private function prepararEixo(): array
    {
        $frota = Frota::create(['codigo' => 'I', 'ativa' => true]);
        $trem = Trem::create(['frota_id' => $frota->id, 'codigo' => 'I01', 'ativo' => true]);
        $carro = Carro::create(['trem_id' => $trem->id, 'codigo' => 'I011', 'posicao' => 1]);
        $eixo = Eixo::create(['carro_id' => $carro->id, 'numero' => 1]);
        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-ORIGINAL', 'status' => 'instalado']);
        $motor = Motor::create(['numero_serie' => 'MT-ORIGINAL', 'status' => 'instalado']);
        InstalacaoRodeiro::create([
            'eixo_id' => $eixo->id, 'rodeiro_id' => $rodeiro->id,
            'data_instalacao' => '2026-01-01',
        ]);
        InstalacaoMotor::create([
            'eixo_id' => $eixo->id, 'motor_id' => $motor->id,
            'data_instalacao' => '2026-01-01',
        ]);

        return compact('eixo', 'rodeiro', 'motor');
    }

    /** Uma troca deve atualizar status e guardar instalações ativa e histórica. */
    public function test_tecnico_substitui_rodeiro_preservando_historico(): void
    {
        ['eixo' => $eixo, 'rodeiro' => $antigo] = $this->prepararEixo();
        $novo = Rodeiro::create(['numero_serie' => 'RD-ESTOQUE', 'status' => 'estoque']);
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $eixo, 'tipo' => 'rodeiro',
        ]), [
            'numero_serie_saida' => $antigo->numero_serie,
            'numero_serie_entrada' => $novo->numero_serie,
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada' => 'Troca programada',
        ])->assertRedirect(route('equipamentos.hierarquica', [
            'frota_id' => $eixo->carro->trem->frota_id,
            'trem_id' => $eixo->carro->trem_id,
            'carro_id' => $eixo->carro_id,
        ]));

        $this->assertDatabaseHas('rodeiros', ['id' => $antigo->id, 'status' => 'em_manutencao']);
        $this->assertDatabaseHas('rodeiros', ['id' => $novo->id, 'status' => 'instalado']);
        $this->assertSame(2, InstalacaoRodeiro::where('eixo_id', $eixo->id)->count());
        $this->assertSame(1, InstalacaoRodeiro::where('eixo_id', $eixo->id)->whereNull('data_retirada')->count());
        $this->assertDatabaseHas('instalacoes_rodeiros', [
            'rodeiro_id' => $antigo->id, 'motivo_retirada' => 'Troca programada',
        ]);
    }

    /** O fluxo de motor aplica a mesma regra transacional. */
    public function test_administrador_substitui_motor(): void
    {
        ['eixo' => $eixo, 'motor' => $antigo] = $this->prepararEixo();
        $novo = Motor::create(['numero_serie' => 'MT-ESTOQUE', 'status' => 'estoque']);
        $usuario = User::factory()->create(['perfil' => 'administrador']);

        $this->actingAs($usuario)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $eixo, 'tipo' => 'motor',
        ]), [
            'numero_serie_saida' => $antigo->numero_serie,
            'numero_serie_entrada' => $novo->numero_serie,
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada' => 'Defeito identificado',
        ])->assertRedirect();

        $this->assertDatabaseHas('motores', ['id' => $antigo->id, 'status' => 'em_manutencao']);
        $this->assertDatabaseHas('motores', ['id' => $novo->id, 'status' => 'instalado']);
        $this->assertSame(1, InstalacaoMotor::where('eixo_id', $eixo->id)->whereNull('data_retirada')->count());
    }

    /** O middleware impede que um gestor realize movimentos. */
    public function test_gestor_nao_pode_movimentar(): void
    {
        ['eixo' => $eixo] = $this->prepararEixo();
        $usuario = User::factory()->create(['perfil' => 'gestor']);

        $this->actingAs($usuario)
            ->get(route('equipamentos.movimentacao.create', ['eixo' => $eixo, 'tipo' => 'motor']))
            ->assertForbidden();
        $this->actingAs($usuario)
            ->post(route('equipamentos.movimentacao.store', ['eixo' => $eixo, 'tipo' => 'motor']))
            ->assertForbidden();

        $this->actingAs($usuario)->get(route('equipamentos.historico', $eixo))->assertOk();
    }

    /** Não deve instalar peça que já está em uso: não ocorre atualização parcial. */
    public function test_impede_equipamento_fora_do_estoque(): void
    {
        ['eixo' => $eixo, 'rodeiro' => $antigo] = $this->prepararEixo();
        $outro = Rodeiro::create(['numero_serie' => 'RD-OCUPADO', 'status' => 'instalado']);
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $eixo, 'tipo' => 'rodeiro',
        ]), [
            'numero_serie_saida' => $antigo->numero_serie,
            'numero_serie_entrada' => $outro->numero_serie,
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada' => 'Troca programada',
        ])->assertSessionHasErrors('numero_serie_entrada');

        $this->assertDatabaseHas('rodeiros', ['id' => $antigo->id, 'status' => 'instalado']);
        $this->assertSame(1, InstalacaoRodeiro::where('eixo_id', $eixo->id)->whereNull('data_retirada')->count());
    }

    /** Confirmação de saída protege contra formulários desatualizados. */
    public function test_rejeita_serie_saida_diferente_da_instalada(): void
    {
        ['eixo' => $eixo, 'rodeiro' => $antigo] = $this->prepararEixo();
        $novo = Rodeiro::create(['numero_serie' => 'RD-ESTOQUE', 'status' => 'estoque']);
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $eixo, 'tipo' => 'rodeiro',
        ]), [
            'numero_serie_saida' => 'RD-INCORRETO',
            'numero_serie_entrada' => $novo->numero_serie,
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada' => 'Troca programada',
        ])->assertSessionHasErrors('numero_serie_saida');

        $this->assertDatabaseHas('rodeiros', ['id' => $antigo->id, 'status' => 'instalado']);
        $this->assertDatabaseHas('rodeiros', ['id' => $novo->id, 'status' => 'estoque']);
    }

    /** Não aceita data anterior à instalação inicial. */
    public function test_impede_data_de_retirada_anterior_a_instalacao(): void
    {
        ['eixo' => $eixo, 'rodeiro' => $antigo] = $this->prepararEixo();
        $novo = Rodeiro::create(['numero_serie' => 'RD-ESTOQUE', 'status' => 'estoque']);
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $eixo, 'tipo' => 'rodeiro',
        ]), [
            'numero_serie_saida' => $antigo->numero_serie,
            'numero_serie_entrada' => $novo->numero_serie,
            'data_movimentacao' => '2025-12-31',
            'motivo_retirada' => 'Troca programada',
        ])->assertSessionHasErrors('data_movimentacao');

        $this->assertSame(1, InstalacaoRodeiro::where('eixo_id', $eixo->id)->whereNull('data_retirada')->count());
    }
}
