<?php

namespace Tests\Feature;

use App\Models\Carro;
use App\Models\Eixo;
use App\Models\Frota;
use App\Models\InstalacaoRodeiro;
use App\Models\MotivoRetirada;
use App\Models\Rodeiro;
use App\Models\Trem;
use App\Models\User;
use Database\Seeders\MotivosRetiradaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MotivosRetiradaTest extends TestCase
{
    use RefreshDatabase;

    /** O catálogo contém nove motivos de rodeiro e cinco de motor. */
    public function test_carga_de_motivos_e_idempotente(): void
    {
        $this->seed(MotivosRetiradaSeeder::class);
        $this->seed(MotivosRetiradaSeeder::class);

        $this->assertSame(9, MotivoRetirada::where('tipo_equipamento', 'rodeiro')->count());
        $this->assertSame(5, MotivoRetirada::where('tipo_equipamento', 'motor')->count());
        $this->assertSame(14, MotivoRetirada::count());
    }

    /** O formulário não lista os motivos do outro tipo de equipamento. */
    public function test_formulario_restringe_motivos_ao_tipo(): void
    {
        $this->seed(MotivosRetiradaSeeder::class);
        $frota = Frota::create(['codigo' => 'I']);
        $trem = Trem::create(['frota_id' => $frota->id, 'codigo' => 'I01']);
        $carro = Carro::create(['trem_id' => $trem->id, 'codigo' => 'I011', 'posicao' => 1]);
        $eixo = Eixo::create(['carro_id' => $carro->id, 'numero' => 1]);
        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-1', 'status' => 'instalado']);
        InstalacaoRodeiro::create([
            'eixo_id' => $eixo->id, 'rodeiro_id' => $rodeiro->id,
            'data_instalacao' => '2026-01-01',
        ]);

        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $this->actingAs($usuario)->get(route('equipamentos.movimentacao.create', [
            'eixo' => $eixo, 'tipo' => 'rodeiro',
        ]))->assertOk()
            ->assertSee('Defeito no rolamento do mancal')
            ->assertDontSee('Motor desbalanceado');
    }

    /** Mesmo manipulando o POST, um motivo de motor não serve para rodeiro. */
    public function test_rejeita_motivo_do_tipo_incorreto_sem_alterar_equipamento(): void
    {
        $this->seed(MotivosRetiradaSeeder::class);
        $frota = Frota::create(['codigo' => 'I']);
        $trem = Trem::create(['frota_id' => $frota->id, 'codigo' => 'I01']);
        $carro = Carro::create(['trem_id' => $trem->id, 'codigo' => 'I011', 'posicao' => 1]);
        $eixo = Eixo::create(['carro_id' => $carro->id, 'numero' => 1]);
        $antigo = Rodeiro::create(['numero_serie' => 'RD-ATUAL', 'status' => 'instalado']);
        $novo = Rodeiro::create(['numero_serie' => 'RD-NOVO', 'status' => 'estoque']);
        InstalacaoRodeiro::create([
            'eixo_id' => $eixo->id, 'rodeiro_id' => $antigo->id,
            'data_instalacao' => '2026-01-01',
        ]);

        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $motivoMotor = MotivoRetirada::where('codigo', 'MOT001')->firstOrFail();

        $this->actingAs($usuario)->post(route('equipamentos.movimentacao.store', [
            'eixo' => $eixo, 'tipo' => 'rodeiro',
        ]), [
            'numero_serie_saida' => $antigo->numero_serie,
            'numero_serie_entrada' => $novo->numero_serie,
            'data_movimentacao' => '2026-11-01',
            'motivo_retirada_id' => $motivoMotor->id,
        ])->assertSessionHasErrors('motivo_retirada_id');

        $this->assertDatabaseHas('rodeiros', ['id' => $antigo->id, 'status' => 'instalado']);
        $this->assertDatabaseHas('rodeiros', ['id' => $novo->id, 'status' => 'estoque']);
        $this->assertSame(1, InstalacaoRodeiro::where('eixo_id', $eixo->id)->whereNull('data_retirada')->count());
    }
}
