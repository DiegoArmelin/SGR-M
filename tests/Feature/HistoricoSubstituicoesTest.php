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

/** Testa agregações, autorização e exportação imprimível da aba de histórico. */
class HistoricoSubstituicoesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MotivosRetiradaSeeder::class);
    }

    /** Cria um histórico controlado; não altera instalações reais. */
    private function registrar(string $frotaCodigo, string $tremCodigo, string $tipo, string $motivoCodigo, string $data): void
    {
        $frota = Frota::firstOrCreate(['codigo' => $frotaCodigo]);
        $trem = Trem::firstOrCreate(['codigo' => $tremCodigo], ['frota_id' => $frota->id]);
        $carro = Carro::firstOrCreate(['codigo' => $tremCodigo.'1'], [
            'trem_id' => $trem->id, 'posicao' => 1,
        ]);
        $eixo = Eixo::firstOrCreate(['carro_id' => $carro->id, 'numero' => $tipo === 'rodeiro' ? 1 : 2]);
        $motivo = MotivoRetirada::where('codigo', $motivoCodigo)->firstOrFail();

        $modelo = $tipo === 'rodeiro' ? Rodeiro::class : Motor::class;
        $instalacaoModelo = $tipo === 'rodeiro' ? InstalacaoRodeiro::class : InstalacaoMotor::class;
        $chave = $tipo === 'rodeiro' ? 'rodeiro_id' : 'motor_id';

        $serie = ($tipo === 'rodeiro' ? 'RD-' : 'MT-') . $frotaCodigo . '-' . $data;
        $equipamento = $modelo::firstOrCreate(['numero_serie' => $serie], ['status' => 'inativo']);
        $instalacaoModelo::create([
            'eixo_id' => $eixo->id,
            $chave => $equipamento->id,
            'data_instalacao' => now()->subYears(3)->toDateString(),
            'data_retirada' => $data,
            'motivo_retirada_id' => $motivo->id,
            'motivo_retirada' => $motivo->descricao,
        ]);
    }

    public function test_acesso_a_historico_e_exportacao_exige_login(): void
    {
        $this->get(route('dashboard.historico'))->assertRedirect(route('login'));
        $this->get(route('dashboard.historico.imprimir'))->assertRedirect(route('login'));

        foreach (['administrador','gestor','tecnico'] as $perfil) {
            $this->actingAs(User::factory()->create(['perfil' => $perfil]))
                ->get(route('dashboard.historico'))->assertOk()
                ->assertSee('Histórico de substituições');
        }
    }

    public function test_graficos_agregam_motivos_e_frotas_com_filtros(): void
    {
        $mes = now()->subMonth()->startOfMonth()->addDays(5)->toDateString();
        $this->registrar('I','I01','rodeiro','ROD006',$mes);
        $this->registrar('I','I01','motor','MOT006',$mes);
        $this->registrar('J','J26','rodeiro','ROD004',$mes);

        $autor = User::factory()->create(['perfil' => 'gestor']);
        $this->actingAs($autor)->get(route('dashboard.historico', ['frota' => 'I']))
            ->assertOk()
            ->assertViewHas('dados', fn ($d) =>
                $d['total'] === 2
                && $d['totais']['rodeiro'] === 1
                && $d['totais']['motor'] === 1
                && count($d['meses']) === 6
                && count($d['frotas']) === 1
                && $d['frotas']['I']['rodeiro'] === 1
                && $d['frotas']['I']['motor'] === 1
            )
            ->assertSee('Motivos das substituições')
            ->assertSee('Substituições por frota');

        $idMotivo = MotivoRetirada::where('codigo','ROD006')->value('id');
        $this->get(route('dashboard.historico', [
            'frota' => 'I', 'motivo_id' => $idMotivo,
        ]))->assertViewHas('dados', fn ($d) =>
            $d['total'] === 1 && $d['totais']['motor'] === 0
        );
    }

    public function test_filtro_data_exclui_historico_antigo_e_pdf_respeita_filtros(): void
    {
        $recente = now()->subMonth()->startOfMonth()->addDays(2)->toDateString();
        $antigo = now()->subMonths(9)->startOfMonth()->addDays(2)->toDateString();
        $this->registrar('I','I01','rodeiro','ROD004',$recente);
        $this->registrar('J','J26','motor','MOT001',$antigo);

        $autor = User::factory()->create(['perfil'=>'gestor']);
        $filtro = ['frota'=>'I', 'inicio'=>now()->subMonths(2)->toDateString()];
        $this->actingAs($autor)->get(route('dashboard.historico', $filtro))
            ->assertOk()->assertViewHas('dados', fn ($d) => $d['total'] === 1);

        $this->get(route('dashboard.historico.imprimir', $filtro))
            ->assertOk()
            ->assertSee('Imprimir / Salvar como PDF')
            ->assertSee('Frota: I')
            ->assertSee('Diâmetro condenado')
            ->assertSee('Substituições mensais');
    }

    public function test_rejeita_data_invertida_e_motivo_inexistente(): void
    {
        $this->actingAs(User::factory()->create(['perfil'=>'gestor']));
        $this->get(route('dashboard.historico', [
            'inicio' => '2026-11-20', 'fim' => '2026-11-01',
        ]))->assertSessionHasErrors('fim');

        $this->get(route('dashboard.historico.imprimir', [
            'motivo_id' => 999999,
        ]))->assertSessionHasErrors('motivo_id');
    }
}
