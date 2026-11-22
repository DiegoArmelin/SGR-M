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

class ConsultaHierarquicaTest extends TestCase
{
    use RefreshDatabase;

    /** Visitantes não podem acessar dados de equipamentos. */
    public function test_visitante_e_redirecionado_ao_login(): void
    {
        $this->get(route('equipamentos.hierarquica'))->assertRedirect(route('login'));
    }

    /** Os três perfis autenticados podem consultar a mesma página. */
    public function test_perfis_autenticados_podem_consultar_frotas(): void
    {
        Frota::create(['codigo' => 'I']);

        foreach (['administrador', 'gestor', 'tecnico'] as $perfil) {
            $usuario = User::factory()->create(['perfil' => $perfil]);

            $this->actingAs($usuario)
                ->get(route('equipamentos.hierarquica'))
                ->assertOk()
                ->assertSee('Frota I');
        }
    }

    /** Uma seleção legítima exibe os números de série e condições do rodeiro. */
    public function test_exibe_apenas_instalacoes_ativas_do_carro_selecionado(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $frota = Frota::create(['codigo' => 'I']);
        $trem = Trem::create(['frota_id' => $frota->id, 'codigo' => 'I01']);
        $carro = Carro::create(['trem_id' => $trem->id, 'codigo' => 'I011', 'posicao' => 1]);
        $eixo = Eixo::create(['carro_id' => $carro->id, 'numero' => 1]);

        $rodeiro = Rodeiro::create([
            'numero_serie' => 'RD-ATIVO-001', 'status' => 'instalado',
            'diametro_lr' => 750.12, 'diametro_lo' => 749.80, 'periodicidade' => 87.5,
        ]);
        $motor = Motor::create(['numero_serie' => 'MT-ATIVO-001', 'status' => 'instalado']);
        $antigo = Rodeiro::create(['numero_serie' => 'RD-ANTIGO-002', 'status' => 'em_manutencao']);

        InstalacaoRodeiro::create([
            'eixo_id' => $eixo->id, 'rodeiro_id' => $rodeiro->id, 'data_instalacao' => '2026-01-01',
        ]);
        InstalacaoMotor::create([
            'eixo_id' => $eixo->id, 'motor_id' => $motor->id, 'data_instalacao' => '2026-01-01',
        ]);
        InstalacaoRodeiro::create([
            'eixo_id' => $eixo->id, 'rodeiro_id' => $antigo->id,
            'data_instalacao' => '2025-01-01', 'data_retirada' => '2025-12-31',
        ]);

        $this->actingAs($usuario)
            ->get(route('equipamentos.hierarquica', [
                'frota_id' => $frota->id, 'trem_id' => $trem->id, 'carro_id' => $carro->id,
            ]))
            ->assertOk()
            ->assertSee('RD-ATIVO-001')
            ->assertSee('MT-ATIVO-001')
            ->assertSee('750,12')
            ->assertSee('87,50')
            ->assertDontSee('RD-ANTIGO-002');
    }

    /** Não permite combinar carro e trem de composições diferentes. */
    public function test_rejeita_carro_que_nao_pertence_ao_trem(): void
    {
        $usuario = User::factory()->create(['perfil' => 'gestor']);
        $frota = Frota::create(['codigo' => 'I']);
        $tremA = Trem::create(['frota_id' => $frota->id, 'codigo' => 'I01']);
        $tremB = Trem::create(['frota_id' => $frota->id, 'codigo' => 'I02']);
        $carroB = Carro::create(['trem_id' => $tremB->id, 'codigo' => 'I021', 'posicao' => 1]);

        $this->actingAs($usuario)->get(route('equipamentos.hierarquica', [
            'frota_id' => $frota->id, 'trem_id' => $tremA->id, 'carro_id' => $carroB->id,
        ]))->assertNotFound();
    }

    /** Um trem de outra frota não pode ser consultado como se pertencesse à primeira. */
    public function test_rejeita_trem_de_outra_frota(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $frotaI = Frota::create(['codigo' => 'I']);
        $frotaJ = Frota::create(['codigo' => 'J']);
        $tremJ = Trem::create(['frota_id' => $frotaJ->id, 'codigo' => 'J26']);

        $this->actingAs($usuario)->get(route('equipamentos.hierarquica', [
            'frota_id' => $frotaI->id, 'trem_id' => $tremJ->id,
        ]))->assertNotFound();
    }

    /** Parâmetros incompletos ou inexistentes devem ser rejeitados. */
    public function test_rejeita_filtros_invalidos(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)
            ->get(route('equipamentos.hierarquica', ['frota_id' => 'abc']))
            ->assertSessionHasErrors('frota_id');

        $this->actingAs($usuario)
            ->get(route('equipamentos.hierarquica', ['trem_id' => 1]))
            ->assertNotFound();
    }
}
