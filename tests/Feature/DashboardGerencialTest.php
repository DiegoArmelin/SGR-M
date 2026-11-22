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

/**
 * Verifica agregações de dashboard no banco de testes e filtros independentes.
 * Não modifica a base MySQL utilizada no Laragon.
 */
class DashboardGerencialTest extends TestCase
{
    use RefreshDatabase;

    /** Prepara frota, trem, carro, eixo e instalações ativas dos dois tipos. */
    private function instalar(string $frotaCodigo, string $tremCodigo): array
    {
        $frota = Frota::firstOrCreate(['codigo' => $frotaCodigo]);
        $trem = Trem::firstOrCreate(
            ['codigo' => $tremCodigo], ['frota_id' => $frota->id]
        );
        $carro = Carro::firstOrCreate(
            ['codigo' => $tremCodigo . '1'],
            ['trem_id' => $trem->id, 'posicao' => 1]
        );
        $eixo = Eixo::firstOrCreate(['carro_id' => $carro->id, 'numero' => 1]);
        $rodeiro = Rodeiro::create([
            'numero_serie' => 'RD-' . $tremCodigo,
            'status' => 'instalado',
            'diametro_lr' => 741,
            'diametro_lo' => 742,
            'periodicidade' => 109,
        ]);
        $motor = Motor::create([
            'numero_serie' => 'MT-' . $tremCodigo,
            'status' => 'instalado',
            'periodicidade' => 111,
        ]);
        $ir = InstalacaoRodeiro::create([
            'eixo_id' => $eixo->id, 'rodeiro_id' => $rodeiro->id,
            'data_instalacao' => '2026-01-01',
        ]);
        $im = InstalacaoMotor::create([
            'eixo_id' => $eixo->id, 'motor_id' => $motor->id,
            'data_instalacao' => '2026-01-01',
        ]);
        return compact('eixo', 'rodeiro', 'motor', 'ir', 'im');
    }

    private function gestor(): User
    {
        return User::factory()->create(['perfil' => 'gestor']);
    }

    /** Página protegida e disponível para três tipos de usuários. */
    public function test_acesso_restrito_a_usuarios_autenticados(): void
    {
        $this->get(route('dashboard.gerencial'))->assertRedirect(route('login'));
        foreach (['administrador', 'gestor', 'tecnico'] as $perfil) {
            $this->actingAs(User::factory()->create(['perfil' => $perfil]))
                ->get(route('dashboard.gerencial'))
                ->assertOk()
                ->assertSee('Dashboard Gerencial do SGR-M');
        }
    }

    /** Condições são contadas só para instalações ativas. */
    public function test_contabiliza_instalados_condicoes_e_diametro_critico(): void
    {
        $this->instalar('I', 'I01');
        $resposta = $this->actingAs($this->gestor())
            ->get(route('dashboard.gerencial'))->assertOk();

        $resposta->assertViewHas('dados', fn ($d) =>
            $d['instalados']['rodeiro'] === 1 &&
            $d['instalados']['motor'] === 1 &&
            $d['periodicidade']['rodeiro'] === 1 &&
            $d['periodicidade']['motor'] === 1 &&
            $d['diametros']['atencao'] === 1 &&
            $d['diametros']['criticos'] === 1
        );
    }

    /** Duas pendências em um mesmo equipamento contam como uma peça afetada. */
    public function test_conta_ocorrencias_separado_de_equipamentos_distintos(): void
    {
        $local = $this->instalar('I', 'I01');
        $autor = $this->gestor();

        foreach (['critica', 'alta'] as $prioridade) {
            PendenciaEquipamento::create([
                'eixo_id' => $local['eixo']->id,
                'rodeiro_id' => $local['rodeiro']->id,
                'descricao' => 'Falha ' . $prioridade,
                'prioridade' => $prioridade,
                'situacao' => 'aberta',
                'registrado_por' => $autor->id,
            ]);
        }

        $this->actingAs($autor)->get(route('dashboard.gerencial'))
            ->assertOk()->assertViewHas('dados', fn ($d) =>
                $d['pendencias']['rodeiro']['total'] === 2 &&
                $d['pendencias']['rodeiro']['equipamentos'] === 1 &&
                $d['pendencias']['rodeiro']['critica'] === 1 &&
                $d['pendencias']['rodeiro']['alta'] === 1
            );
    }

    /** Data filtra apenas trocas; não altera os indicadores atuais. */
    public function test_filtra_substituicoes_por_frota_e_periodo_sem_afetar_instalados(): void
    {
        $i = $this->instalar('I', 'I01');
        $j = $this->instalar('J', 'J26');
        $i['ir']->update(['data_retirada' => '2026-11-10']);
        $i['im']->update(['data_retirada' => '2026-11-20']);
        $j['ir']->update(['data_retirada' => '2026-11-10']);

        $this->actingAs($this->gestor())->get(route('dashboard.gerencial', [
            'frota' => 'I',
            'inicio' => '2026-11-01',
            'fim' => '2026-11-15',
        ]))->assertOk()->assertViewHas('dados', fn ($d) =>
            $d['substituicoes']['rodeiro'] === 1 &&
            $d['substituicoes']['motor'] === 0 &&
            $d['instalados']['rodeiro'] === 0 &&
            $d['instalados']['motor'] === 0
        );
    }

    /** Data inicial maior que final e frota inexistente são recusadas. */
    public function test_rejeita_filtros_invalidos(): void
    {
        $this->actingAs($this->gestor())
            ->get(route('dashboard.gerencial', [
                'inicio' => '2026-11-20', 'fim' => '2026-11-01',
            ]))->assertSessionHasErrors('fim');

        $this->get(route('dashboard.gerencial', ['frota' => 'NAO_EXISTE']))
            ->assertSessionHasErrors('frota');
    }

    /** A visão geral exibe um único parque e prioriza as pendências graves. */
    public function test_visao_geral_remove_trens_e_mostra_card_30_dias(): void
    {
        $local = $this->instalar('I', 'I01');
        $autor = $this->gestor();

        PendenciaEquipamento::create([
            'eixo_id' => $local['eixo']->id,
            'rodeiro_id' => $local['rodeiro']->id,
            'descricao' => 'Pendência encerrada',
            'prioridade' => 'alta',
            'situacao' => 'resolvida',
            'registrado_por' => $autor->id,
            'resolvida_em' => now()->subDays(5),
        ]);
        PendenciaEquipamento::create([
            'eixo_id' => $local['eixo']->id,
            'rodeiro_id' => $local['rodeiro']->id,
            'descricao' => 'Pendência antiga',
            'prioridade' => 'alta',
            'situacao' => 'resolvida',
            'registrado_por' => $autor->id,
            'resolvida_em' => now()->subDays(40),
        ]);

        $this->actingAs($autor)->get(route('dashboard.gerencial'))
            ->assertOk()
            ->assertSee('Total de equipamentos instalados')
            ->assertSee('Pendências encerradas nos últimos 30 dias')
            ->assertSee('pendências altas')
            ->assertDontSee('Trens cadastrados por frota')
            ->assertDontSee('sem leitura de periodicidade')
            ->assertViewHas('dados', fn ($d) => $d['encerradas30'] === 1);
    }


    /** Tendência compara saldos aberturas-encerramentos em duas janelas de 30 dias. */
    public function test_tendencia_compara_aberturas_e_fechamentos_e_respeita_frota(): void
    {
        $i = $this->instalar('I', 'I01');
        $j = $this->instalar('J', 'J26');
        $autor = $this->gestor();

        // Abriu uma na janela atual, encerrou outra na mesma janela.
        PendenciaEquipamento::create([
            'eixo_id'=>$i['eixo']->id,'rodeiro_id'=>$i['rodeiro']->id,
            'descricao'=>'Aberta recente','prioridade'=>'media',
            'situacao'=>'aberta','registrado_por'=>$autor->id,
        ]);
        $encerrada = PendenciaEquipamento::create([
            'eixo_id'=>$i['eixo']->id,'rodeiro_id'=>$i['rodeiro']->id,
            'descricao'=>'Encerrada recente','prioridade'=>'alta',
            'situacao'=>'resolvida','registrado_por'=>$autor->id,
            'resolvida_em'=>now()->subDays(3),
        ]);
        $encerrada->timestamps = false;
        $encerrada->update([
            'created_at'=>now()->subDays(45),
            'updated_at'=>now()->subDays(3),
        ]);
        $antiga = PendenciaEquipamento::create([
            'eixo_id'=>$i['eixo']->id,'rodeiro_id'=>$i['rodeiro']->id,
            'descricao'=>'Aberta antiga','prioridade'=>'baixa',
            'situacao'=>'aberta','registrado_por'=>$autor->id,
        ]);
        $antiga->timestamps = false;
        $antiga->update([
            'created_at'=>now()->subDays(40),
            'updated_at'=>now()->subDays(40),
        ]);

        // Outra frota não deve contaminar os números da frota I.
        PendenciaEquipamento::create([
            'eixo_id'=>$j['eixo']->id,'rodeiro_id'=>$j['rodeiro']->id,
            'descricao'=>'Outra frota','prioridade'=>'critica',
            'situacao'=>'aberta','registrado_por'=>$autor->id,
        ]);

        $this->actingAs($autor)->get(route('dashboard.gerencial',['frota'=>'I']))
            ->assertOk()->assertViewHas('dados', fn ($dados) =>
                $dados['encerradas30']===1
                && $dados['tendencia']['atual']['abertas']===1
                && $dados['tendencia']['atual']['encerradas']===1
                && $dados['tendencia']['anterior']['abertas']===2
                && $dados['tendencia']['direcao']==='diminuindo'
            )->assertSee('Tendência do saldo de pendências');
    }

}
