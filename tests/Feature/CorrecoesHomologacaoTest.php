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
use App\Services\DashboardGerencialService;
use App\Services\RankingPriorizacaoService;
use Carbon\Carbon;
use Database\Seeders\MotivosRetiradaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regressão dos defeitos D03 a D09, identificados nos testes de ponta a ponta
 * de 26/11/2026 (continuação do registro de defeitos da seção 3.i).
 */
class CorrecoesHomologacaoTest extends TestCase
{
    use RefreshDatabase;

    private const CABECALHO = 'frota;trem;carro;posicao;serie_rodeiro;diametro_lr;diametro_lo;periodicidade;serie_motor;periodicidade_motor;data_atualizacao';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MotivosRetiradaSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Posição I/I01/I011-1 com rodeiro e motor instalados. */
    private function posicao(): array
    {
        $frota = Frota::create(['codigo' => 'I', 'ativa' => true]);
        $trem = Trem::create(['frota_id' => $frota->id, 'codigo' => 'I01', 'ativo' => true]);
        $carro = Carro::create(['trem_id' => $trem->id, 'codigo' => 'I011', 'posicao' => 1]);
        $eixo = Eixo::create(['carro_id' => $carro->id, 'numero' => 1]);
        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-001', 'status' => 'instalado']);
        $motor = Motor::create(['numero_serie' => 'MT-001', 'status' => 'instalado']);
        InstalacaoRodeiro::create(['eixo_id' => $eixo->id, 'rodeiro_id' => $rodeiro->id, 'data_instalacao' => '2026-01-01']);
        InstalacaoMotor::create(['eixo_id' => $eixo->id, 'motor_id' => $motor->id, 'data_instalacao' => '2026-01-01']);

        return compact('frota', 'trem', 'carro', 'eixo', 'rodeiro', 'motor');
    }

    private function usuario(string $perfil): User
    {
        return User::factory()->create(['perfil' => $perfil]);
    }

    private function csv(string $data): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('condicoes.csv', self::CABECALHO . "\n"
            . "I;I01;I011;1;RD-001;790.00;790.00;50.00;MT-001;50.00;{$data}\n");
    }

    private function pendencia(array $p, string $situacao = 'aberta'): PendenciaEquipamento
    {
        return PendenciaEquipamento::create([
            'eixo_id' => $p['eixo']->id, 'rodeiro_id' => $p['rodeiro']->id,
            'motivo_retirada_id' => MotivoRetirada::where('codigo', 'ROD004')->value('id'),
            'descricao' => 'Diâmetro condenado', 'prioridade' => 'critica', 'situacao' => $situacao,
            'registrado_por' => $this->usuario('tecnico')->id,
            'resolvida_em' => $situacao === 'resolvida' ? now() : null,
        ]);
    }

    // ------------------------------------------------------------------ D03

    public function test_d03_administrador_abre_a_edicao_de_trem(): void
    {
        $p = $this->posicao();

        $this->actingAs($this->usuario('administrador'))
            ->get(route('trens.edit', $p['trem']))
            ->assertOk()
            ->assertSee('I01');
    }

    public function test_d03_edicao_lista_frotas_ativas_e_mantem_a_frota_inativa_atual(): void
    {
        $inativa = Frota::create(['codigo' => 'ANTIGA', 'ativa' => false]);
        Frota::create(['codigo' => 'OUTRAINATIVA', 'ativa' => false]);
        Frota::create(['codigo' => 'ATIVA', 'ativa' => true]);
        $trem = Trem::create(['frota_id' => $inativa->id, 'codigo' => 'T01', 'ativo' => true]);

        $this->actingAs($this->usuario('administrador'))
            ->get(route('trens.edit', $trem))
            ->assertOk()
            ->assertSee('ANTIGA')
            ->assertSee('ATIVA')
            ->assertDontSee('OUTRAINATIVA');
    }

    // ------------------------------------------------------------- D04 / D05

    /** @return array<string, array{0: string, 1: string}> */
    public static function datasForaDaFaixa(): array
    {
        return [
            'futura próxima (D05)' => ['2026-12-01 08:00:00', 'data futura'],
            'ano digitado errado (D05)' => ['2030-01-01 00:00:00', 'data futura'],
            'após 2038 (D04)' => ['2099-01-01 00:00:00', 'data futura'],
            'antes de 1970 (D04)' => ['1960-01-01 00:00:00', 'anterior a 01/01/2000'],
        ];
    }

    #[DataProvider('datasForaDaFaixa')]
    public function test_d04_d05_importacao_recusa_data_fora_da_faixa_sem_erro_500(string $data, string $mensagem): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-27 10:00:00', 'America/Sao_Paulo'));
        $p = $this->posicao();

        $this->actingAs($this->usuario('administrador'))
            ->from(route('importacoes.periodicidade.create'))
            ->post(route('importacoes.periodicidade.store'), ['arquivo' => $this->csv($data)])
            ->assertRedirect(route('importacoes.periodicidade.create'))
            ->assertSessionHasErrors('arquivo');

        $this->assertStringContainsString($mensagem, session('errors')->first('arquivo'));
        $this->assertStringContainsString('Linha 2', session('errors')->first('arquivo'));
        $this->assertNull($p['rodeiro']->fresh()->data_atualizacao_condicao);
        $this->assertNull($p['motor']->fresh()->data_atualizacao_condicao);
    }

    public function test_d05_data_futura_nao_trava_a_medicao_real_seguinte(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-27 10:00:00', 'America/Sao_Paulo'));
        $p = $this->posicao();
        $admin = $this->usuario('administrador');

        $this->actingAs($admin)->post(route('importacoes.periodicidade.store'), ['arquivo' => $this->csv('2030-01-01 00:00:00')]);
        $this->actingAs($admin)->post(route('importacoes.periodicidade.store'), ['arquivo' => $this->csv('2026-11-25 10:00:00')])
            ->assertSessionHasNoErrors();

        $rodeiro = $p['rodeiro']->fresh();
        $this->assertSame('2026-11-25 10:00:00', $rodeiro->data_atualizacao_condicao->format('Y-m-d H:i:s'));
        $this->assertSame('50.00', $rodeiro->periodicidade);
    }

    public function test_d05_aceita_data_atual_dentro_da_tolerancia(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-27 10:00:00', 'America/Sao_Paulo'));
        $p = $this->posicao();

        $this->actingAs($this->usuario('administrador'))
            ->post(route('importacoes.periodicidade.store'), ['arquivo' => $this->csv('2026-11-27 10:03:00')])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($p['rodeiro']->fresh()->data_atualizacao_condicao);
    }

    // ------------------------------------------------------------------ D06

    public function test_d06_aplicacao_usa_horario_de_brasilia(): void
    {
        $this->assertSame('America/Sao_Paulo', config('app.timezone'));
    }

    public function test_d06_as_22h30_de_brasilia_a_troca_nao_aceita_a_data_de_amanha(): void
    {
        // 26/11/2026 22:30 em Brasília = 27/11/2026 01:30 UTC.
        Carbon::setTestNow(Carbon::parse('2026-11-27 01:30:00', 'UTC'));
        $p = $this->posicao();
        Rodeiro::create(['numero_serie' => 'RD-ESTOQUE', 'status' => 'estoque']);
        $tecnico = $this->usuario('tecnico');
        $url = route('equipamentos.movimentacao.store', ['eixo' => $p['eixo'], 'tipo' => 'rodeiro']);
        $dados = [
            'numero_serie_saida' => 'RD-001', 'numero_serie_entrada' => 'RD-ESTOQUE',
            'motivo_retirada_id' => MotivoRetirada::where('codigo', 'ROD005')->value('id'),
        ];

        $this->actingAs($tecnico)
            ->get(route('equipamentos.movimentacao.create', ['eixo' => $p['eixo'], 'tipo' => 'rodeiro']))
            ->assertSee('max="2026-11-26"', false);

        $this->actingAs($tecnico)->post($url, $dados + ['data_movimentacao' => '2026-11-27'])
            ->assertSessionHasErrors('data_movimentacao');
        $this->assertSame('instalado', $p['rodeiro']->fresh()->status);

        $this->actingAs($tecnico)->post($url, $dados + ['data_movimentacao' => '2026-11-26'])
            ->assertSessionHasNoErrors();
        $this->assertSame('em_manutencao', $p['rodeiro']->fresh()->status);
    }

    // ------------------------------------------------------------------ D07

    public function test_d07_consulta_por_serie_exibe_periodicidade_do_motor(): void
    {
        $p = $this->posicao();
        $p['motor']->update(['periodicidade' => 98.00]);

        $this->actingAs($this->usuario('gestor'))
            ->get(route('equipamentos.index', ['tipo' => 'motor', 'numero_serie' => 'MT-001']))
            ->assertOk()
            ->assertSee('Periodicidade (%)')
            ->assertSee('98,00');
    }

    public function test_d07_consulta_hierarquica_exibe_periodicidade_do_rodeiro_e_do_motor(): void
    {
        $p = $this->posicao();
        $p['rodeiro']->update(['periodicidade' => 88.10]);
        $p['motor']->update(['periodicidade' => 99.50]);

        $this->actingAs($this->usuario('gestor'))
            ->get(route('equipamentos.hierarquica', [
                'frota_id' => $p['frota']->id, 'trem_id' => $p['trem']->id, 'carro_id' => $p['carro']->id,
            ]))
            ->assertOk()
            ->assertSeeInOrder(['Periodicidade do rodeiro (%)', 'Periodicidade do motor (%)'])
            ->assertSeeInOrder(['88,10', 'MT-001', '99,50']);
    }

    // ------------------------------------------------------------------ D08

    private function tentarLogin(string $email, string $senha)
    {
        return $this->from(route('login'))->post(route('login.submit'), ['email' => $email, 'password' => $senha]);
    }

    public function test_d08_falhas_de_um_usuario_nao_bloqueiam_outro_usuario_da_mesma_rede(): void
    {
        $this->usuario('gestor')->update(['email' => 'gestor@sgr-m.test', 'password' => 'senha-correta-123']);
        $admin = User::factory()->create(['perfil' => 'administrador', 'email' => 'admin@sgr-m.test', 'password' => 'senha-correta-123']);

        for ($i = 0; $i < 5; $i++) {
            $this->tentarLogin('gestor@sgr-m.test', 'senha-errada');
        }

        $this->tentarLogin('admin@sgr-m.test', 'senha-correta-123')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_d08_sexta_falha_do_mesmo_usuario_e_bloqueada_com_mensagem_em_portugues(): void
    {
        $this->usuario('gestor')->update(['email' => 'gestor@sgr-m.test', 'password' => 'senha-correta-123']);

        for ($i = 0; $i < 5; $i++) {
            $this->tentarLogin('gestor@sgr-m.test', 'senha-errada')
                ->assertSessionHasErrors(['email' => 'As credenciais informadas são inválidas.']);
        }

        // Bloqueado inclusive com a senha correta, até o fim do minuto.
        $this->tentarLogin('gestor@sgr-m.test', 'senha-correta-123')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertStringContainsString('Muitas tentativas', session('errors')->first('email'));

        $this->travel(61)->seconds();
        $this->tentarLogin('gestor@sgr-m.test', 'senha-correta-123')->assertRedirect(route('dashboard'));
    }

    public function test_d08_logins_corretos_nao_contam_para_o_limite(): void
    {
        $usuario = $this->usuario('tecnico');
        $usuario->update(['email' => 'tecnico@sgr-m.test', 'password' => 'senha-correta-123']);

        for ($i = 0; $i < 7; $i++) {
            $this->tentarLogin('tecnico@sgr-m.test', 'senha-correta-123')->assertRedirect(route('dashboard'));
            $this->post(route('logout'));
        }
    }

    // ------------------------------------------------------------------ D09

    public function test_d09_administrador_cancela_pendencia_com_justificativa(): void
    {
        $p = $this->posicao();
        $pendencia = $this->pendencia($p);
        $admin = $this->usuario('administrador');

        $this->actingAs($admin)
            ->from(route('pendencias.index'))
            ->patch(route('pendencias.cancelar', $pendencia), ['motivo_cancelamento' => 'Registrada no rodeiro; o defeito era no motor.'])
            ->assertRedirect(route('pendencias.index'))
            ->assertSessionHas('success');

        $pendencia->refresh();
        $this->assertSame('cancelada', $pendencia->situacao);
        $this->assertSame($admin->id, $pendencia->cancelada_por);
        $this->assertNotNull($pendencia->cancelada_em);
        $this->assertSame('Registrada no rodeiro; o defeito era no motor.', $pendencia->motivo_cancelamento);
        $this->assertNull($pendencia->resolvida_em);
    }

    public function test_d09_justificativa_e_obrigatoria(): void
    {
        $pendencia = $this->pendencia($this->posicao());

        $this->actingAs($this->usuario('administrador'))
            ->patch(route('pendencias.cancelar', $pendencia), ['motivo_cancelamento' => 'curta'])
            ->assertSessionHasErrors('motivo_cancelamento');

        $this->assertSame('aberta', $pendencia->fresh()->situacao);
    }

    public function test_d09_somente_administrador_cancela(): void
    {
        $pendencia = $this->pendencia($this->posicao());

        foreach (['tecnico', 'gestor'] as $perfil) {
            $this->actingAs($this->usuario($perfil))
                ->patch(route('pendencias.cancelar', $pendencia), ['motivo_cancelamento' => 'Tentativa sem permissão.'])
                ->assertForbidden();
        }
        $this->assertSame('aberta', $pendencia->fresh()->situacao);
    }

    public function test_d09_pendencia_resolvida_nao_pode_ser_cancelada(): void
    {
        $pendencia = $this->pendencia($this->posicao(), 'resolvida');

        $this->actingAs($this->usuario('administrador'))
            ->patch(route('pendencias.cancelar', $pendencia), ['motivo_cancelamento' => 'Tentativa sobre pendência encerrada.'])
            ->assertSessionHasErrors('motivo_cancelamento');

        $this->assertSame('resolvida', $pendencia->fresh()->situacao);
    }

    public function test_d09_cancelada_sai_do_ranking_e_dos_indicadores_e_fica_no_historico(): void
    {
        $p = $this->posicao();
        $pendencia = $this->pendencia($p);
        $admin = $this->usuario('administrador');

        $antes = app(RankingPriorizacaoService::class)->consultar('rodeiro', []);
        $this->assertSame(1, $antes['totalPendenciasGeral']);

        $this->actingAs($admin)->patch(route('pendencias.cancelar', $pendencia), [
            'motivo_cancelamento' => 'Registrada no rodeiro; o defeito era no motor.',
        ]);

        $depois = app(RankingPriorizacaoService::class)->consultar('rodeiro', []);
        $this->assertSame(0, $depois['totalPendenciasGeral']);

        $dashboard = app(DashboardGerencialService::class)->consultar(null, null, null);
        $this->assertSame(0, $dashboard['pendencias']['rodeiro']['total']);
        $this->assertSame(0, $dashboard['tendencia']['atual']['abertas']);

        $this->actingAs($admin)->get(route('pendencias.index', ['situacao' => 'cancelada']))
            ->assertOk()
            ->assertSee('Cancelada')
            ->assertSee('Registrada no rodeiro; o defeito era no motor.');
    }

    public function test_d09_substituicao_nao_altera_pendencia_cancelada(): void
    {
        $p = $this->posicao();
        $pendencia = $this->pendencia($p);
        $pendencia->update(['situacao' => 'cancelada', 'cancelada_em' => now(), 'motivo_cancelamento' => 'Registro feito por engano.']);
        Rodeiro::create(['numero_serie' => 'RD-ESTOQUE', 'status' => 'estoque']);

        $this->actingAs($this->usuario('tecnico'))->post(route('equipamentos.movimentacao.store', ['eixo' => $p['eixo'], 'tipo' => 'rodeiro']), [
            'numero_serie_saida' => 'RD-001', 'numero_serie_entrada' => 'RD-ESTOQUE',
            'data_movimentacao' => now()->toDateString(),
            'motivo_retirada_id' => MotivoRetirada::where('codigo', 'ROD005')->value('id'),
        ])->assertSessionHasNoErrors();

        $this->assertSame('cancelada', $pendencia->fresh()->situacao);
        $this->assertNull($pendencia->fresh()->resolvida_em);
    }

    public function test_d09_lista_mostra_acao_de_cancelar_somente_ao_administrador(): void
    {
        $this->pendencia($this->posicao());

        $this->actingAs($this->usuario('administrador'))->get(route('pendencias.index'))
            ->assertSee('Cancelar registro');
        $this->actingAs($this->usuario('tecnico'))->get(route('pendencias.index'))
            ->assertDontSee('Cancelar registro');
    }
}
