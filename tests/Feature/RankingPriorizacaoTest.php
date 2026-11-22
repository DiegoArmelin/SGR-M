<?php

namespace Tests\Feature;

use App\Models\Carro;
use App\Models\Eixo;
use App\Models\Frota;
use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\PendenciaEquipamento;
use App\Models\Rodeiro;
use App\Models\Trem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Testa a ordem de priorização e a exclusão de equipamentos retirados. */
class RankingPriorizacaoTest extends TestCase
{
    use RefreshDatabase;

    private function instalar(string $frotaCodigo, string $tremCodigo, string $carroCodigo, int $eixoNumero, string $serie, string $tipo = 'rodeiro', array $condicao = []): array
    {
        $frota = Frota::firstOrCreate(['codigo' => $frotaCodigo]);
        $trem = Trem::firstOrCreate(['codigo' => $tremCodigo], ['frota_id' => $frota->id]);
        $carro = Carro::firstOrCreate(['codigo' => $carroCodigo], ['trem_id' => $trem->id, 'posicao' => (int) substr($carroCodigo, -1)]);
        $eixo = Eixo::firstOrCreate(['carro_id' => $carro->id, 'numero' => $eixoNumero]);

        if ($tipo === 'rodeiro') {
            $equipamento = Rodeiro::create(array_merge(['numero_serie' => $serie, 'status' => 'instalado'], $condicao));
            $instalacao = InstalacaoRodeiro::create([
                'eixo_id' => $eixo->id, 'rodeiro_id' => $equipamento->id,
                'data_instalacao' => '2026-01-01',
            ]);
        } else {
            $equipamento = Motor::create(['numero_serie' => $serie, 'status' => 'instalado']);
            $instalacao = InstalacaoMotor::create([
                'eixo_id' => $eixo->id, 'motor_id' => $equipamento->id,
                'data_instalacao' => '2026-01-01',
            ]);
        }

        return compact('eixo', 'equipamento', 'instalacao');
    }

    private function pendencia(array $posicao, string $prioridade, string $tipo = 'rodeiro'): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        PendenciaEquipamento::create([
            'eixo_id' => $posicao['eixo']->id,
            'rodeiro_id' => $tipo === 'rodeiro' ? $posicao['equipamento']->id : null,
            'motor_id' => $tipo === 'motor' ? $posicao['equipamento']->id : null,
            'prioridade' => $prioridade,
            'descricao' => 'Falha ' . $prioridade,
            'situacao' => 'aberta',
            'registrado_por' => $usuario->id,
        ]);
    }

    public function test_visitantes_nao_acessam_e_tres_perfis_podem_consultar(): void
    {
        $this->get(route('ranking.index'))->assertRedirect(route('login'));

        foreach (['administrador', 'gestor', 'tecnico'] as $perfil) {
            $this->actingAs(User::factory()->create(['perfil' => $perfil]))
                ->get(route('ranking.index'))
                ->assertOk()
                ->assertSee('Ranking de priorização');
        }
    }

    public function test_pendencia_critica_precede_alertas_e_equipamento_nao_duplicado(): void
    {
        $critico = $this->instalar('I', 'I01', 'I011', 1, 'RD-CRITICO', 'rodeiro', [
            'periodicidade' => 120, 'diametro_lr' => 742, 'diametro_lo' => 743,
        ]);
        $periodico = $this->instalar('I', 'I01', 'I011', 2, 'RD-PERIODICO', 'rodeiro', [
            'periodicidade' => 102,
        ]);
        $diametro = $this->instalar('I', 'I01', 'I011', 3, 'RD-DIAMETRO', 'rodeiro', [
            'diametro_lr' => 744, 'diametro_lo' => 750,
        ]);
        $this->pendencia($critico, 'critica');
        $this->pendencia($critico, 'media');

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index'))
            ->assertOk()
            ->assertSeeInOrder(['RD-CRITICO', 'RD-PERIODICO', 'RD-DIAMETRO'])
            ->assertSee('Pendência critica')
            ->assertSee('Periodicidade atingiu')
            ->assertSee('Menor diâmetro')
            ->assertViewHas('total', 3);

        $this->assertSame(2, PendenciaEquipamento::count());
    }

    public function test_periodicidade_100_e_diametro_abaixo_de_745_entram_no_ranking(): void
    {
        $this->instalar('I', 'I01', 'I011', 1, 'RD-100', 'rodeiro', ['periodicidade' => 100]);
        $this->instalar('I', 'I01', 'I011', 2, 'RD-744', 'rodeiro', ['diametro_lr' => 744.99, 'diametro_lo' => 745]);
        $this->instalar('I', 'I01', 'I011', 3, 'RD-745', 'rodeiro', ['diametro_lr' => 745, 'diametro_lo' => 750, 'periodicidade' => 99]);

        $this->actingAs(User::factory()->create(['perfil' => 'tecnico']))
            ->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('RD-100')
            ->assertSee('RD-744')
            ->assertDontSee('RD-745')
            ->assertViewHas('total', 2);
    }

    public function test_filtra_por_frota_e_tipo_de_equipamento(): void
    {
        $rodeiroI = $this->instalar('I', 'I01', 'I011', 1, 'RD-I', 'rodeiro', ['periodicidade' => 105]);
        $motorI = $this->instalar('I', 'I01', 'I011', 1, 'MT-I', 'motor');
        $rodeiroJ = $this->instalar('J', 'J26', 'J261', 1, 'RD-J', 'rodeiro', ['periodicidade' => 106]);
        $this->pendencia($motorI, 'alta', 'motor');

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index', ['frota' => 'I', 'tipo' => 'motor']))
            ->assertOk()
            ->assertSee('MT-I')
            ->assertDontSee('RD-I')
            ->assertDontSee('RD-J')
            ->assertViewHas('total', 1);
    }

    public function test_equipmentos_retirados_e_pendencias_resolvidas_nao_aparecem(): void
    {
        $retirado = $this->instalar('I', 'I01', 'I011', 1, 'RD-RETIRADO', 'rodeiro', [
            'periodicidade' => 120, 'diametro_lr' => 740,
        ]);
        $retirado['instalacao']->update(['data_retirada' => '2026-11-01']);
        $resolvido = $this->instalar('I', 'I01', 'I011', 2, 'RD-RESOLVIDO');
        $this->pendencia($resolvido, 'critica');
        PendenciaEquipamento::where('rodeiro_id', $resolvido['equipamento']->id)
            ->update(['situacao' => 'resolvida', 'resolvida_em' => now()]);

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index'))
            ->assertOk()
            ->assertDontSee('RD-RETIRADO')
            ->assertDontSee('RD-RESOLVIDO')
            ->assertViewHas('total', 0);
    }
}
