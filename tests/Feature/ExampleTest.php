<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_inicial_redireciona_visitante_para_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_pagina_inicial_redireciona_usuario_autenticado_para_dashboard(): void
    {
        $usuario = User::factory()->create(['perfil' => 'tecnico']);

        $this->actingAs($usuario)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }
}
