<?php

namespace Tests\Feature;

use App\Models\Carro;
use App\Models\Eixo;
use App\Models\Frota;
use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\MotivoRetirada;
use App\Models\PendenciaEquipamento;
use App\Models\Rodeiro;
use App\Models\Trem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\MotivosRetiradaSeeder;
use Tests\TestCase;

class PendenciaEquipamentoTest extends TestCase
{
    use RefreshDatabase;

    /** Cada teste utiliza o catálogo real de motivos em banco temporário. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MotivosRetiradaSeeder::class);
    }

    /**
     * Cria a hierarquia usada pelos testes, incluindo instalações ativas.
     * Essa preparação simula a consulta real de rodeiros e motores por eixo.
     */
    private function prepararPosicao(): array
    {
        $frota = Frota::create(['codigo' => 'I']);
        $trem = Trem::create(['frota_id' => $frota->id, 'codigo' => 'I01']);
        $carro = Carro::create(['trem_id' => $trem->id, 'codigo' => 'I011', 'posicao' => 1]);
        $eixo = Eixo::create(['carro_id' => $carro->id, 'numero' => 1]);
        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-001', 'status' => 'instalado']);
        $motor = Motor::create(['numero_serie' => 'MT-001', 'status' => 'instalado']);

        InstalacaoRodeiro::create([
            'eixo_id' => $eixo->id, 'rodeiro_id' => $rodeiro->id,
            'data_instalacao' => '2026-01-01',
        ]);
        InstalacaoMotor::create([
            'eixo_id' => $eixo->id, 'motor_id' => $motor->id,
            'data_instalacao' => '2026-01-01',
        ]);

        return compact('frota', 'trem', 'carro', 'eixo', 'rodeiro', 'motor');
    }

    /** Monta um formulário POST completo com os IDs da posição. */
    private function dados(array $local, string $tipo = 'rodeiro'): array
    {
        return [
            'frota_id' => $local['frota']->id,
            'trem_id' => $local['trem']->id,
            'carro_id' => $local['carro']->id,
            'eixo_id' => $local['eixo']->id,
            'tipo' => $tipo,
            // A seleção do motivo substitui a digitação da descrição genérica.
            'motivo_retirada_id' => MotivoRetirada::where('codigo', $tipo === 'rodeiro' ? 'ROD001' : 'MOT001')->value('id'),
            'observacoes' => 'Análise inicial registrada no SGR-M.',
            'prioridade' => 'critica',
        ];
    }

    public function test_visitante_e_redirecionado_ao_login(): void
    {
        $this->get(route('pendencias.index'))->assertRedirect(route('login'));
        $this->get(route('pendencias.create'))->assertRedirect(route('login'));
    }

    public function test_todos_os_perfis_podem_consultar_e_registrar(): void
    {
        foreach (['administrador', 'gestor', 'tecnico'] as $perfil) {
            $usuario = User::factory()->create(['perfil' => $perfil]);
            $this->actingAs($usuario)->get(route('pendencias.index'))->assertOk();
            $this->actingAs($usuario)->get(route('pendencias.create'))->assertOk();
        }
    }

    /** Ao escolher a posição, a página mostra a série instalada automaticamente. */
    public function test_formulario_reconhece_equipamento_instalado_por_posicao(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)->get(route('pendencias.create', [
            'frota_id' => $local['frota']->id,
            'trem_id' => $local['trem']->id,
            'carro_id' => $local['carro']->id,
            'eixo_id' => $local['eixo']->id,
            'tipo' => 'rodeiro',
        ]))->assertOk()
            ->assertSee('RD-001')
            ->assertSee('Defeito no rolamento do mancal')
            ->assertDontSee('Motor desbalanceado')
            ->assertDontSee('MT-001');
    }

    /** Persistência da posição e identificação do equipamento instalado. */
    public function test_registra_pendencia_de_rodeiro_pela_posicao(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)->post(route('pendencias.store'), $this->dados($local))
            ->assertRedirect(route('pendencias.index'));

        $this->assertDatabaseHas('pendencias_equipamentos', [
            'eixo_id' => $local['eixo']->id,
            'rodeiro_id' => $local['rodeiro']->id,
            'motor_id' => null,
            'registrado_por' => $usuario->id,
            'prioridade' => 'critica',
            'situacao' => 'aberta',
            'motivo_retirada_id' => MotivoRetirada::where('codigo', 'ROD001')->value('id'),
            'observacoes' => 'Análise inicial registrada no SGR-M.',
        ]);
    }

    public function test_registra_pendencia_de_motor_pela_posicao(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'gestor']);

        $this->actingAs($usuario)->post(route('pendencias.store'), $this->dados($local, 'motor'))
            ->assertRedirect(route('pendencias.index'));

        $this->assertDatabaseHas('pendencias_equipamentos', [
            'eixo_id' => $local['eixo']->id,
            'motor_id' => $local['motor']->id,
            'rodeiro_id' => null,
        ]);
    }

    /** Impede adulterar campos hidden para relacionar um eixo à frota errada. */
    public function test_rejeita_hierarquia_inconsistente(): void
    {
        $local = $this->prepararPosicao();
        $outraFrota = Frota::create(['codigo' => 'J']);
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $dados = $this->dados($local);
        $dados['frota_id'] = $outraFrota->id;

        $this->actingAs($usuario)->post(route('pendencias.store'), $dados)
            ->assertSessionHasErrors('eixo_id');

        $this->assertSame(0, PendenciaEquipamento::count());
    }

    /** Rejeita eixo cujo equipamento do tipo solicitado já foi removido. */
    public function test_rejeita_posicao_sem_instalacao_ativa(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        InstalacaoMotor::where('eixo_id', $local['eixo']->id)
            ->update(['data_retirada' => '2026-11-01']);

        $this->actingAs($usuario)->post(
            route('pendencias.store'),
            $this->dados($local, 'motor')
        )->assertSessionHasErrors('eixo_id');

        $this->assertSame(0, PendenciaEquipamento::count());
    }

    /** Um motivo de motor não deve ser aceito para pendência de rodeiro. */
    public function test_rejeita_motivo_de_outro_tipo(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $dados = $this->dados($local);
        $dados['motivo_retirada_id'] = MotivoRetirada::where('codigo', 'MOT001')->value('id');

        $this->actingAs($usuario)->post(route('pendencias.store'), $dados)
            ->assertSessionHasErrors('motivo_retirada_id');

        $this->assertSame(0, PendenciaEquipamento::count());
    }

    /** O cadastro deve exigir motivo mas permitir observações em branco. */
    public function test_motivo_obrigatorio_e_observacoes_opcionais(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $dados = $this->dados($local);
        unset($dados['motivo_retirada_id'], $dados['observacoes']);

        $this->actingAs($usuario)->post(route('pendencias.store'), $dados)
            ->assertSessionHasErrors('motivo_retirada_id');

        $dados['motivo_retirada_id'] = MotivoRetirada::where('codigo', 'ROD001')->value('id');
        $this->actingAs($usuario)->post(route('pendencias.store'), $dados)
            ->assertRedirect(route('pendencias.index'));

        $this->assertDatabaseHas('pendencias_equipamentos', [
            'eixo_id' => $local['eixo']->id,
            'descricao' => 'Defeito no rolamento do mancal',
            'observacoes' => null,
        ]);
    }

    /** A listagem oferece substituição apenas ao perfil autorizado. */
    public function test_lista_mostra_substituir_e_nao_permte_resolver_manualmente(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        PendenciaEquipamento::create([
            'eixo_id' => $local['eixo']->id,
            'rodeiro_id' => $local['rodeiro']->id,
            'descricao' => 'Defeito cadastrado',
            'prioridade' => 'critica',
            'registrado_por' => $usuario->id,
        ]);

        $this->actingAs($usuario)->get(route('pendencias.index'))
            ->assertOk()
            ->assertSee('Substituir')
            ->assertSee('I011-1')
            ->assertSee('Crítica')
            ->assertDontSee('Justificativa');

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('pendencias.resolver'));
    }

    /** Ao substituir o equipamento, a pendência da posição é resolvida. */
    public function test_substituicao_encerra_pendencia_automaticamente(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $novo = Rodeiro::create(['numero_serie' => 'RD-NOVO', 'status' => 'estoque']);
        $pendencia = PendenciaEquipamento::create([
            'eixo_id' => $local['eixo']->id,
            'rodeiro_id' => $local['rodeiro']->id,
            'descricao' => 'Falha no mancal',
            'prioridade' => 'critica',
            'registrado_por' => $usuario->id,
        ]);

        $this->actingAs($usuario)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $local['eixo'], 'tipo' => 'rodeiro',
        ]), [
            'numero_serie_saida' => $local['rodeiro']->numero_serie,
            'numero_serie_estoque' => $novo->numero_serie,
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada_id' => MotivoRetirada::where('codigo', 'ROD001')->value('id'),
            'origem' => 'pendencias',
        ])->assertRedirect(route('pendencias.index'));

        $this->assertDatabaseHas('pendencias_equipamentos', [
            'id' => $pendencia->id, 'situacao' => 'resolvida',
            'rodeiro_id' => $local['rodeiro']->id,
        ]);
        $this->assertNotNull($pendencia->fresh()->resolvida_em);
        $this->assertDatabaseHas('rodeiros', ['id' => $novo->id, 'status' => 'instalado']);
        $this->assertSame(1, InstalacaoRodeiro::where('eixo_id', $local['eixo']->id)
            ->whereNull('data_retirada')->count());
    }

    /** A troca de rodeiro não encerra pendência do motor do mesmo eixo. */
    public function test_substituicao_nao_encerra_pendencia_de_outro_tipo(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $novo = Rodeiro::create(['numero_serie' => 'RD-NOVO', 'status' => 'estoque']);
        $motorPendente = PendenciaEquipamento::create([
            'eixo_id' => $local['eixo']->id,
            'motor_id' => $local['motor']->id,
            'descricao' => 'Problema no motor',
            'prioridade' => 'media',
            'registrado_por' => $usuario->id,
        ]);

        $this->actingAs($usuario)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $local['eixo'], 'tipo' => 'rodeiro',
        ]), [
            'numero_serie_saida' => $local['rodeiro']->numero_serie,
            'numero_serie_estoque' => $novo->numero_serie,
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada_id' => MotivoRetirada::where('codigo', 'ROD001')->value('id'),
        ])->assertRedirect();

        $this->assertSame('aberta', $motorPendente->fresh()->situacao);
    }

    /** Se a substituição for rejeitada, a pendência não pode encerrar. */
    public function test_troca_invalida_mantem_pendencia_aberta(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $pendencia = PendenciaEquipamento::create([
            'eixo_id' => $local['eixo']->id,
            'rodeiro_id' => $local['rodeiro']->id,
            'descricao' => 'Falha detectada',
            'prioridade' => 'alta',
            'registrado_por' => $usuario->id,
        ]);

        $this->actingAs($usuario)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $local['eixo'], 'tipo' => 'rodeiro',
        ]), [
            'numero_serie_saida' => $local['rodeiro']->numero_serie,
            'numero_serie_entrada' => 'RD-NAO-CADASTRADO',
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada_id' => MotivoRetirada::where('codigo', 'ROD001')->value('id'),
        ])->assertSessionHasErrors('numero_serie_entrada');

        $this->assertSame('aberta', $pendencia->fresh()->situacao);
        $this->assertDatabaseHas('rodeiros', [
            'id' => $local['rodeiro']->id, 'status' => 'instalado',
        ]);
    }

    public function test_lista_ordenada_por_prioridade(): void
    {
        $local = $this->prepararPosicao();
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        foreach (['baixa', 'critica', 'media'] as $prioridade) {
            PendenciaEquipamento::create([
                'eixo_id' => $local['eixo']->id,
                'motor_id' => $local['motor']->id,
                'descricao' => 'Teste ' . $prioridade,
                'prioridade' => $prioridade,
                'registrado_por' => $usuario->id,
            ]);
        }

        $this->actingAs($usuario)->get(route('pendencias.index'))
            ->assertOk()->assertSeeInOrder(['Teste critica', 'Teste media', 'Teste baixa']);
    }
}
