<?php

namespace Tests\Feature;

use App\Models\Motor;
use App\Models\PendenciaEquipamento;
use App\Models\Rodeiro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendenciaEquipamentoTest extends TestCase
{
    use RefreshDatabase;

    /** Visitantes não podem consultar o módulo. */
    public function test_visitante_e_redirecionado_ao_login(): void
    {
        $this->get(route('pendencias.index'))->assertRedirect(route('login'));
        $this->get(route('pendencias.create'))->assertRedirect(route('login'));
    }

    /** Todos os perfis definidos na matriz do projeto registram pendências. */
    public function test_perfis_podem_acessar_pendencias(): void
    {
        foreach (['administrador', 'gestor', 'tecnico'] as $perfil) {
            $usuario = User::factory()->create(['perfil' => $perfil]);
            $this->actingAs($usuario)->get(route('pendencias.index'))->assertOk();
            $this->actingAs($usuario)->get(route('pendencias.create'))->assertOk();
        }
    }

    /** O cadastro deve preservar o número de série do rodeiro e o autor. */
    public function test_registra_pendencia_critica_de_rodeiro(): void
    {
        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-001', 'status' => 'instalado']);
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)->post(route('pendencias.store'), [
            'tipo' => 'rodeiro',
            'numero_serie' => 'RD-001',
            'descricao' => 'Vibração acima do esperado',
            'prioridade' => 'critica',
        ])->assertRedirect(route('pendencias.index'));

        $this->assertDatabaseHas('pendencias_equipamentos', [
            'rodeiro_id' => $rodeiro->id, 'motor_id' => null,
            'registrado_por' => $usuario->id,
            'prioridade' => 'critica', 'situacao' => 'aberta',
        ]);
    }

    /** A pendência de motor não deve ser associada a rodeiro. */
    public function test_registra_pendencia_de_motor(): void
    {
        $motor = Motor::create(['numero_serie' => 'MT-001', 'status' => 'instalado']);
        $usuario = User::factory()->create(['perfil' => 'gestor']);

        $this->actingAs($usuario)->post(route('pendencias.store'), [
            'tipo' => 'motor',
            'numero_serie' => $motor->numero_serie,
            'descricao' => 'Ruído anormal',
            'prioridade' => 'alta',
        ])->assertRedirect(route('pendencias.index'));

        $this->assertDatabaseHas('pendencias_equipamentos', [
            'motor_id' => $motor->id, 'rodeiro_id' => null, 'prioridade' => 'alta',
        ]);
    }

    /** Séries inexistentes não podem gerar pendências órfãs. */
    public function test_rejeita_serie_inexistente(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)->post(route('pendencias.store'), [
            'tipo' => 'motor',
            'numero_serie' => 'NAO-EXISTE',
            'descricao' => 'Defeito',
            'prioridade' => 'media',
        ])->assertSessionHasErrors('numero_serie');

        $this->assertSame(0, PendenciaEquipamento::count());
    }

    /** A resolução registra evidência e preserva o vínculo ao equipamento. */
    public function test_resolve_pendencia_com_justificativa(): void
    {
        $usuario = User::factory()->create(['perfil' => 'administrador']);
        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-002', 'status' => 'instalado']);
        $pendencia = PendenciaEquipamento::create([
            'rodeiro_id' => $rodeiro->id,
            'descricao' => 'Pendência de teste',
            'prioridade' => 'media',
            'situacao' => 'aberta',
            'registrado_por' => $usuario->id,
        ]);

        $this->actingAs($usuario)->patch(route('pendencias.resolver', $pendencia), [
            'observacoes_resolucao' => 'Inspeção concluída e problema corrigido.',
        ])->assertRedirect(route('pendencias.index'));

        $this->assertDatabaseHas('pendencias_equipamentos', [
            'id' => $pendencia->id,
            'situacao' => 'resolvida',
            'rodeiro_id' => $rodeiro->id,
        ]);
        $this->assertNotNull($pendencia->fresh()->resolvida_em);
    }

    /** Pendência resolvida não pode ser encerrada uma segunda vez. */
    public function test_impede_resolver_pendencia_novamente(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $motor = Motor::create(['numero_serie' => 'MT-003', 'status' => 'instalado']);
        $pendencia = PendenciaEquipamento::create([
            'motor_id' => $motor->id,
            'descricao' => 'Pendência encerrada',
            'prioridade' => 'baixa',
            'situacao' => 'resolvida',
            'registrado_por' => $usuario->id,
            'resolvida_em' => now(),
        ]);

        $this->actingAs($usuario)->patch(route('pendencias.resolver', $pendencia), [
            'observacoes_resolucao' => 'Nova tentativa',
        ])->assertSessionHasErrors('situacao');
    }

    /** A priorização deve listar críticas antes de médias e baixas. */
    public function test_lista_ordenada_por_prioridade(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $motor = Motor::create(['numero_serie' => 'MT-004', 'status' => 'instalado']);

        foreach (['baixa', 'critica', 'media'] as $prioridade) {
            PendenciaEquipamento::create([
                'motor_id' => $motor->id, 'descricao' => 'Teste ' . $prioridade,
                'prioridade' => $prioridade, 'registrado_por' => $usuario->id,
            ]);
        }

        $response = $this->actingAs($usuario)->get(route('pendencias.index'));
        $response->assertOk()->assertSeeInOrder(['Teste critica', 'Teste media', 'Teste baixa']);
    }
}
