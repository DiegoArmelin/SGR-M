<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes do menu superior: navegação acessível e itens administrativos
 * disponibilizados somente para o perfil autorizado.
 */
class MenuSuperiorTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_visualiza_menu_de_cadastros_e_importacao(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administração')
            ->assertSee('Frotas')
            ->assertSee('Trens')
            ->assertSee('Equipamentos cadastrados')
            ->assertSee('Importar condições (CSV)')
            ->assertSee(route('importacoes.periodicidade.create'))
            ->assertSee(route('frotas.index'))
            ->assertSee(route('trens.index'));
    }

    public function test_tecnico_e_gestor_nao_visualizam_dropdown_administrativo(): void
    {
        foreach (['tecnico', 'gestor'] as $perfil) {
            $usuario = User::factory()->create(['perfil' => $perfil]);
            $this->actingAs($usuario)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee('Administração')
                ->assertDontSee('Importar condições (CSV)')
                ->assertSee('Consultas')
                ->assertSee('Pendências')
                ->assertSee('Ranking')
                ->assertSee('Indicadores');
        }
    }

    public function test_menu_nao_substitui_controle_de_autorizacao_das_rotas(): void
    {
        $gestor = User::factory()->create(['perfil' => 'gestor']);
        $this->actingAs($gestor)
            ->get(route('importacoes.periodicidade.create'))
            ->assertForbidden();

        $this->get(route('frotas.create'))->assertForbidden();
        $this->get(route('trens.create'))->assertForbidden();
    }

    public function test_consulta_de_equipamentos_continua_acessivel_a_todos_os_perfis(): void
    {
        foreach (['administrador', 'gestor', 'tecnico'] as $perfil) {
            $usuario = User::factory()->create(['perfil' => $perfil]);
            $this->actingAs($usuario)
                ->get(route('equipamentos.index'))
                ->assertOk()
                ->assertSee('Consulta por número de série');
        }
    }
}
