<?php

namespace Tests\Feature;

use App\Models\Carro;
use App\Models\Eixo;
use App\Models\Frota;
use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\MotivoRetirada;
use App\Models\Rodeiro;
use App\Models\Trem;
use App\Models\User;
use Database\Seeders\MotivosRetiradaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Garante que a interface permita escolher uma série em estoque
 * ou digitá-la manualmente, preservando as regras de substituição.
 */
class SelecaoEstoqueMovimentacaoTest extends TestCase
{
    use RefreshDatabase;

    private function preparar(): array
    {
        $this->seed(MotivosRetiradaSeeder::class);

        $frota = Frota::create(['codigo' => 'I']);
        $trem = Trem::create(['frota_id' => $frota->id, 'codigo' => 'I01']);
        $carro = Carro::create(['trem_id' => $trem->id, 'codigo' => 'I011', 'posicao' => 1]);
        $eixo = Eixo::create(['carro_id' => $carro->id, 'numero' => 1]);

        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-ATUAL', 'status' => 'instalado']);
        InstalacaoRodeiro::create([
            'eixo_id' => $eixo->id, 'rodeiro_id' => $rodeiro->id,
            'data_instalacao' => '2026-01-01',
        ]);

        $motor = Motor::create(['numero_serie' => 'MT-ATUAL', 'status' => 'instalado']);
        InstalacaoMotor::create([
            'eixo_id' => $eixo->id, 'motor_id' => $motor->id,
            'data_instalacao' => '2026-01-01',
        ]);

        return compact('eixo', 'rodeiro', 'motor');
    }

    /** A lista mostra apenas itens do tipo selecionado com status estoque. */
    public function test_lista_exibe_somente_equipamentos_em_estoque_do_tipo(): void
    {
        ['eixo' => $eixo] = $this->preparar();
        Rodeiro::create(['numero_serie' => 'RD-LIVRE', 'status' => 'estoque']);
        Rodeiro::create(['numero_serie' => 'RD-MANUTENCAO', 'status' => 'em_manutencao']);
        Motor::create(['numero_serie' => 'MT-LIVRE', 'status' => 'estoque']);

        $tecnico = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($tecnico)->get(route('equipamentos.movimentacao.create', [
            'eixo' => $eixo, 'tipo' => 'rodeiro',
        ]))
            ->assertOk()
            ->assertSee('RD-LIVRE')
            ->assertDontSee('RD-MANUTENCAO')
            ->assertDontSee('MT-LIVRE');

        $this->actingAs($tecnico)->get(route('equipamentos.movimentacao.create', [
            'eixo' => $eixo, 'tipo' => 'motor',
        ]))
            ->assertOk()
            ->assertSee('MT-LIVRE')
            ->assertDontSee('RD-LIVRE');
    }

    /** A seleção na lista substitui o rodeiro sem exigir digitação manual. */
    public function test_substitui_rodeiro_escolhido_na_lista(): void
    {
        ['eixo' => $eixo, 'rodeiro' => $antigo] = $this->preparar();
        $novo = Rodeiro::create(['numero_serie' => 'RD-LIVRE', 'status' => 'estoque']);
        $tecnico = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($tecnico)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $eixo, 'tipo' => 'rodeiro',
        ]), [
            'numero_serie_saida' => $antigo->numero_serie,
            'numero_serie_estoque' => $novo->numero_serie,
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada_id' => MotivoRetirada::where('codigo', 'ROD005')->value('id'),
        ])->assertRedirect();

        $this->assertDatabaseHas('rodeiros', ['id' => $novo->id, 'status' => 'instalado']);
        $this->assertDatabaseHas('rodeiros', ['id' => $antigo->id, 'status' => 'em_manutencao']);
    }

    /** A série digitada manualmente tem prioridade se ambos forem preenchidos. */
    public function test_campo_manual_prevalece_sobre_lista(): void
    {
        ['eixo' => $eixo, 'motor' => $antigo] = $this->preparar();
        $daLista = Motor::create(['numero_serie' => 'MT-LISTA', 'status' => 'estoque']);
        $manual = Motor::create(['numero_serie' => 'MT-MANUAL', 'status' => 'estoque']);
        $tecnico = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($tecnico)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $eixo, 'tipo' => 'motor',
        ]), [
            'numero_serie_saida' => $antigo->numero_serie,
            'numero_serie_estoque' => $daLista->numero_serie,
            'numero_serie_entrada' => $manual->numero_serie,
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada_id' => MotivoRetirada::where('codigo', 'MOT002')->value('id'),
        ])->assertRedirect();

        $this->assertDatabaseHas('motores', ['id' => $manual->id, 'status' => 'instalado']);
        $this->assertDatabaseHas('motores', ['id' => $daLista->id, 'status' => 'estoque']);
    }

    /** É obrigatório escolher ou digitar uma série. */
    public function test_rejeita_substituicao_sem_nenhuma_serie_de_entrada(): void
    {
        ['eixo' => $eixo, 'rodeiro' => $antigo] = $this->preparar();
        $tecnico = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($tecnico)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $eixo, 'tipo' => 'rodeiro',
        ]), [
            'numero_serie_saida' => $antigo->numero_serie,
            'numero_serie_estoque' => '',
            'numero_serie_entrada' => '',
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada_id' => MotivoRetirada::where('codigo', 'ROD005')->value('id'),
        ])->assertSessionHasErrors(['numero_serie_entrada', 'numero_serie_estoque']);

        $this->assertDatabaseHas('rodeiros', ['id' => $antigo->id, 'status' => 'instalado']);
    }
}
