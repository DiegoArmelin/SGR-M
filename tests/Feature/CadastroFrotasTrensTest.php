<?php

namespace Tests\Feature;

use App\Models\Frota;
use App\Models\Trem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CadastroFrotasTrensTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_nao_acessa_frotas(): void
    {
        $this->get(route('frotas.index'))->assertRedirect(route('login'));
    }

    public function test_tecnico_consulta_mas_nao_cadastra_frotas(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $this->actingAs($usuario)->get(route('frotas.index'))->assertOk();
        $this->actingAs($usuario)->post(route('frotas.store'), ['codigo' => 'X'])->assertForbidden();
        $this->assertDatabaseMissing('frotas', ['codigo' => 'X']);
    }

    public function test_gestor_consulta_trens_mas_nao_cadastra(): void
    {
        $usuario = User::factory()->create(['perfil' => 'gestor']);
        $this->actingAs($usuario)->get(route('trens.index'))->assertOk();
        $this->actingAs($usuario)->post(route('trens.store'), ['codigo' => 'X01'])->assertForbidden();
    }

    public function test_administrador_cadastra_frota_e_trem(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);
        $this->actingAs($admin)->post(route('frotas.store'), [
            'codigo' => 'I',
            'descricao' => 'Frota fictícia I',
            'ativa' => 1,
        ])->assertRedirect(route('frotas.index'));

        $frota = Frota::where('codigo', 'I')->firstOrFail();

        $this->actingAs($admin)->post(route('trens.store'), [
            'frota_id' => $frota->id,
            'codigo' => 'I01',
            'ativo' => 1,
        ])->assertRedirect(route('trens.index'));

        $this->assertDatabaseHas('trens', ['codigo' => 'I01', 'frota_id' => $frota->id]);
    }

    public function test_codigo_duplicado_e_rejeitado(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);
        Frota::create(['codigo' => 'J', 'ativa' => true]);

        $this->actingAs($admin)->post(route('frotas.store'), [
            'codigo' => 'J',
            'ativa' => 1,
        ])->assertSessionHasErrors('codigo');
    }

    public function test_frota_com_trem_nao_pode_ser_excluida(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);
        $frota = Frota::create(['codigo' => 'L', 'ativa' => true]);
        Trem::create(['frota_id' => $frota->id, 'codigo' => 'L01', 'ativo' => true]);

        $this->actingAs($admin)->delete(route('frotas.destroy', $frota))
            ->assertSessionHasErrors('exclusao');
        $this->assertDatabaseHas('frotas', ['id' => $frota->id]);
    }

    public function test_nao_cadastra_trem_em_frota_inativa(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);
        $frota = Frota::create(['codigo' => 'M', 'ativa' => false]);

        $this->actingAs($admin)->post(route('trens.store'), [
            'frota_id' => $frota->id,
            'codigo' => 'M01',
            'ativo' => 1,
        ])->assertSessionHasErrors('frota_id');
        $this->assertDatabaseMissing('trens', ['codigo' => 'M01']);
    }

    public function test_filtro_de_trens_por_frota(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);
        $a = Frota::create(['codigo' => 'A']);
        $b = Frota::create(['codigo' => 'B']);
        Trem::create(['frota_id' => $a->id, 'codigo' => 'A01']);
        Trem::create(['frota_id' => $b->id, 'codigo' => 'B01']);

        $this->actingAs($usuario)->get(route('trens.index', ['frota_id' => $a->id]))
            ->assertOk()->assertSee('A01')->assertDontSee('B01');
    }
}
