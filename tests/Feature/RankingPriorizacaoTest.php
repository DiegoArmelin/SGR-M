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

/** Testes da visão em abas com tabelas e filtros independentes. */
class RankingPriorizacaoTest extends TestCase
{
    use RefreshDatabase;

    /** Monta uma posição com equipamento instalado e número de série único. */
    private function instalar(
        string $frotaCodigo, string $tremCodigo, string $carroCodigo,
        int $numeroEixo, string $serie, string $tipo = 'rodeiro',
        array $condicao = []
    ): array {
        $frota = Frota::firstOrCreate(['codigo' => $frotaCodigo]);
        $trem = Trem::firstOrCreate(
            ['codigo' => $tremCodigo], ['frota_id' => $frota->id]
        );
        $carro = Carro::firstOrCreate(
            ['codigo' => $carroCodigo],
            ['trem_id' => $trem->id, 'posicao' => (int) substr($carroCodigo, -1)]
        );
        $eixo = Eixo::firstOrCreate([
            'carro_id' => $carro->id, 'numero' => $numeroEixo,
        ]);

        if ($tipo === 'rodeiro') {
            $equipamento = Rodeiro::create(array_merge([
                'numero_serie' => $serie, 'status' => 'instalado',
            ], $condicao));
            $instalacao = InstalacaoRodeiro::create([
                'eixo_id' => $eixo->id, 'rodeiro_id' => $equipamento->id,
                'data_instalacao' => '2026-01-01',
            ]);
        } else {
            $equipamento = Motor::create([
                'numero_serie' => $serie, 'status' => 'instalado',
            ]);
            $instalacao = InstalacaoMotor::create([
                'eixo_id' => $eixo->id, 'motor_id' => $equipamento->id,
                'data_instalacao' => '2026-01-01',
            ]);
        }

        return compact('eixo', 'equipamento', 'instalacao');
    }

    private function criarPendencia(array $local, string $prioridade, string $tipo = 'rodeiro'): void
    {
        $autor = User::factory()->create(['perfil' => 'tecnico']);
        PendenciaEquipamento::create([
            'eixo_id' => $local['eixo']->id,
            'rodeiro_id' => $tipo === 'rodeiro' ? $local['equipamento']->id : null,
            'motor_id' => $tipo === 'motor' ? $local['equipamento']->id : null,
            'prioridade' => $prioridade,
            'descricao' => 'Ocorrência ' . $prioridade,
            'situacao' => 'aberta',
            'registrado_por' => $autor->id,
        ]);
    }

    public function test_autenticacao_e_abas_para_os_tres_perfis(): void
    {
        $this->get(route('ranking.index'))->assertRedirect(route('login'));

        foreach (['administrador', 'gestor', 'tecnico'] as $perfil) {
            $this->actingAs(User::factory()->create(['perfil' => $perfil]))
                ->get(route('ranking.index'))
                ->assertOk()
                ->assertSee('Rodeiros')
                ->assertSee('Motores')
                ->assertSee('Pendências técnicas')
                ->assertSee('Diâmetros dos rodeiros')
                ->assertSee('Periodicidade dos rodeiros');
        }
    }

    public function test_aba_motores_exibe_somente_pendencias_de_motores(): void
    {
        $rodeiro = $this->instalar('I', 'I01', 'I011', 1, 'RD-I', 'rodeiro', [
            'periodicidade' => 120, 'diametro_lr' => 740,
        ]);
        $motor = $this->instalar('I', 'I01', 'I011', 1, 'MT-I', 'motor');
        $this->criarPendencia($rodeiro, 'critica');
        $this->criarPendencia($motor, 'alta', 'motor');

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index', ['tipo' => 'motor']))
            ->assertOk()
            ->assertSee('MT-I')
            ->assertDontSee('RD-I')
            ->assertDontSee('Diâmetros dos rodeiros')
            ->assertSee('Periodicidade dos motores')
            ->assertViewHas('totalPendencias', 1);
    }

    public function test_pendencias_sao_ordenadas_por_criticidade_independentemente_de_medicoes(): void
    {
        $baixa = $this->instalar('I', 'I01', 'I011', 1, 'RD-BAIXA', 'rodeiro', [
            'periodicidade' => 120, 'diametro_lr' => 740,
        ]);
        $alta = $this->instalar('I', 'I01', 'I011', 2, 'RD-ALTA');
        $critica = $this->instalar('I', 'I01', 'I011', 3, 'RD-CRITICA');
        $this->criarPendencia($baixa, 'baixa');
        $this->criarPendencia($alta, 'alta');
        $this->criarPendencia($critica, 'critica');

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index'))
            ->assertOk()
            ->assertSeeInOrder(['RD-CRITICA', 'RD-ALTA', 'RD-BAIXA'])
            ->assertViewHas('totalPendencias', 3);
    }

    public function test_diametros_sao_ordenados_do_menor_e_cards_contabilizam_todos(): void
    {
        $this->instalar('I', 'I01', 'I011', 1, 'RD-744', 'rodeiro', [
            'diametro_lr' => 744, 'diametro_lo' => 746,
        ]);
        $this->instalar('I', 'I01', 'I011', 2, 'RD-740', 'rodeiro', [
            'diametro_lr' => 742, 'diametro_lo' => 740,
        ]);
        $this->instalar('I', 'I01', 'I011', 3, 'RD-745', 'rodeiro', [
            'diametro_lr' => 745, 'diametro_lo' => 745,
        ]);
        $this->instalar('I', 'I01', 'I011', 4, 'RD-742', 'rodeiro', [
            'diametro_lr' => 742, 'diametro_lo' => 742.5,
        ]);

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index'))
            ->assertOk()
            ->assertSeeInOrder(['RD-740', 'RD-742', 'RD-744'])
            ->assertDontSee('RD-745')
            ->assertViewHas('totalDiametros', 3)
            ->assertViewHas('diametrosCriticos', 2);
    }

    public function test_periodicidade_ordenada_do_maior_percentual(): void
    {
        $this->instalar('I', 'I01', 'I011', 1, 'RD-101', 'rodeiro', ['periodicidade' => 101]);
        $this->instalar('I', 'I01', 'I011', 2, 'RD-120', 'rodeiro', ['periodicidade' => 120]);
        $this->instalar('I', 'I01', 'I011', 3, 'RD-100', 'rodeiro', ['periodicidade' => 100]);
        $this->instalar('I', 'I01', 'I011', 4, 'RD-099', 'rodeiro', ['periodicidade' => 99]);

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index'))
            ->assertOk()
            ->assertSeeInOrder(['RD-120', 'RD-101', 'RD-100'])
            ->assertDontSee('RD-099')
            ->assertViewHas('totalPeriodicidades', 3);
    }

    public function test_filtro_de_diametro_nao_afeta_periodicidade_ou_pendencias(): void
    {
        $a = $this->instalar('I', 'I01', 'I011', 1, 'RD-I1', 'rodeiro', [
            'diametro_lr' => 740, 'periodicidade' => 105,
        ]);
        $this->instalar('J', 'J26', 'J261', 1, 'RD-J1', 'rodeiro', [
            'diametro_lr' => 741, 'periodicidade' => 110,
        ]);
        $this->criarPendencia($a, 'critica');

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index', ['d_trem' => 'J26']))
            ->assertOk()
            ->assertViewHas('totalDiametros', 1)
            ->assertViewHas('totalPeriodicidades', 2)
            ->assertViewHas('totalPendencias', 1);
    }

    public function test_filtros_de_trem_e_carro_e_rejeicao_de_combinacao_invalida(): void
    {
        $this->instalar('I', 'I01', 'I011', 1, 'RD-I01', 'rodeiro', ['diametro_lr' => 742]);
        $this->instalar('J', 'J26', 'J261', 1, 'RD-J26', 'rodeiro', ['diametro_lr' => 743]);

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index', ['d_trem' => 'I01', 'd_carro' => 'I011']))
            ->assertOk()
            ->assertViewHas('totalDiametros', 1);

        $this->get(route('ranking.index', [
            'd_trem' => 'I01', 'd_carro' => 'J261',
        ]))->assertNotFound();
    }

    public function test_cada_tabela_limita_a_exibicao_a_dez_registros(): void
    {
        $autor = User::factory()->create(['perfil' => 'tecnico']);

        // Doze carros diferentes para evitar duplicidade de instalação ativa.
        for ($i = 1; $i <= 12; $i++) {
            $tremCodigo = 'I' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            $carroCodigo = $tremCodigo . '1';
            $local = $this->instalar('I', $tremCodigo, $carroCodigo, 1, 'RD-TOP-' . $i, 'rodeiro', [
                'diametro_lr' => 740 + $i / 10,
                'diametro_lo' => 741 + $i / 10,
                'periodicidade' => 100 + $i,
            ]);
            PendenciaEquipamento::create([
                'eixo_id' => $local['eixo']->id,
                'rodeiro_id' => $local['equipamento']->id,
                'descricao' => 'Falha',
                'prioridade' => 'alta',
                'situacao' => 'aberta',
                'registrado_por' => $autor->id,
            ]);
        }

        $resposta = $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index'))->assertOk()
            ->assertViewHas('totalPendencias', 12)
            ->assertViewHas('totalDiametros', 12)
            ->assertViewHas('totalPeriodicidades', 12);

        $resposta->assertViewHas('pendencias', fn ($v) => $v->count() === 10);
        $resposta->assertViewHas('diametros', fn ($v) => $v->count() === 10);
        $resposta->assertViewHas('periodicidades', fn ($v) => $v->count() === 10);
    }

    public function test_equipamentos_retirados_e_pendencias_resolvidas_nao_entram(): void
    {
        $retirado = $this->instalar('I', 'I01', 'I011', 1, 'RD-RETIRADO', 'rodeiro', [
            'periodicidade' => 120, 'diametro_lr' => 740,
        ]);
        $retirado['instalacao']->update(['data_retirada' => '2026-11-01']);
        $resolvido = $this->instalar('I', 'I01', 'I011', 2, 'RD-RESOLVIDO');
        $this->criarPendencia($resolvido, 'critica');
        PendenciaEquipamento::where('rodeiro_id', $resolvido['equipamento']->id)
            ->update(['situacao' => 'resolvida', 'resolvida_em' => now()]);

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index'))
            ->assertOk()
            ->assertDontSee('RD-RETIRADO')
            ->assertDontSee('RD-RESOLVIDO')
            ->assertViewHas('totalDiametros', 0)
            ->assertViewHas('totalPeriodicidades', 0)
            ->assertViewHas('totalPendencias', 0);
    }

    /** Os cards de pendência mostram totais por criticidade na localização filtrada. */
    public function test_cards_de_pendencias_por_criticidade(): void
    {
        $critico = $this->instalar('I', 'I01', 'I011', 1, 'RD-C1');
        $alto = $this->instalar('I', 'I01', 'I011', 2, 'RD-A1');
        $medio = $this->instalar('J', 'J26', 'J261', 1, 'RD-M1');
        $this->criarPendencia($critico, 'critica');
        $this->criarPendencia($alto, 'alta');
        $this->criarPendencia($medio, 'media');

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index', ['p_frota' => 'I']))
            ->assertOk()
            ->assertViewHas('totalPendencias', 2)
            ->assertViewHas('pendenciasPorPrioridade', fn ($v) =>
                $v['critica'] === 1 && $v['alta'] === 1
                && $v['media'] === 0 && $v['baixa'] === 0);
    }

    /** Motores com periodicidade são classificados pelas mesmas faixas. */
    public function test_periodicidade_de_motores_e_cards_100_105_110(): void
    {
        $a = $this->instalar('I', 'I01', 'I011', 1, 'MT-100', 'motor');
        $b = $this->instalar('I', 'I01', 'I011', 2, 'MT-105', 'motor');
        $c = $this->instalar('I', 'I01', 'I011', 3, 'MT-110', 'motor');
        $a['equipamento']->update(['periodicidade' => 100]);
        $b['equipamento']->update(['periodicidade' => 105]);
        $c['equipamento']->update(['periodicidade' => 110]);

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index', ['tipo' => 'motor']))
            ->assertOk()
            ->assertSee('Periodicidade dos motores')
            ->assertSeeInOrder(['MT-110', 'MT-105', 'MT-100'])
            ->assertViewHas('totalPeriodicidades', 3)
            ->assertViewHas('periodicidades105', 2)
            ->assertViewHas('periodicidades110', 1);
    }

    /** Cada tabela aplica frota independentemente das demais. */
    public function test_filtros_de_frota_independentes_nas_tres_tabelas(): void
    {
        $i = $this->instalar('I', 'I01', 'I011', 1, 'RD-FI', 'rodeiro', [
            'diametro_lr' => 740, 'periodicidade' => 105,
        ]);
        $j = $this->instalar('J', 'J26', 'J261', 1, 'RD-FJ', 'rodeiro', [
            'diametro_lr' => 741, 'periodicidade' => 110,
        ]);
        $this->criarPendencia($i, 'critica');
        $this->criarPendencia($j, 'alta');

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('ranking.index', ['d_frota' => 'I', 'r_frota' => 'J', 'p_frota' => 'I']))
            ->assertOk()
            ->assertViewHas('totalPendencias', 1)
            ->assertViewHas('totalDiametros', 1)
            ->assertViewHas('totalPeriodicidades', 1);
    }

}
