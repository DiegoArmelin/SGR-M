<?php

namespace Tests\Feature;

use App\Models\Rodeiro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultaEquipamentosTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_e_redirecionado_para_login(): void
    {
        $this->get(route('equipamentos.index'))->assertRedirect(route('login'));
    }

    public function test_usuario_autenticado_consulta_rodeiros_por_numero_de_serie(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        Rodeiro::create(['numero_serie' => 'RD-TESTE-001']);
        Rodeiro::create(['numero_serie' => 'RD-OUTRO-002']);

        $this->actingAs($usuario)
            ->get(route('equipamentos.index', ['tipo' => 'rodeiro', 'numero_serie' => 'TESTE']))
            ->assertOk()
            ->assertSee('RD-TESTE-001')
            ->assertDontSee('RD-OUTRO-002');
    }

    public function test_tipo_invalido_e_rejeitado(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)
            ->get(route('equipamentos.index', ['tipo' => 'invalido']))
            ->assertSessionHasErrors('tipo');
    }
}
