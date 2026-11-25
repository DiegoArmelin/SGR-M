<?php

namespace Tests\Feature;

use App\Models\Frota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/** Garante que as mensagens padrão do Laravel sejam exibidas em português. */
class MensagensPortuguesTest extends TestCase
{
    use RefreshDatabase;

    public function test_aplicacao_utiliza_portugues(): void
    {
        $this->assertSame('pt_BR', app()->getLocale());
    }

    /** Defeito D02: o arquivo inválido exibia "The arquivo field must be...". */
    public function test_arquivo_invalido_na_importacao_exibe_mensagem_em_portugues(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);

        $this->actingAs($admin)
            ->from(route('importacoes.periodicidade.create'))
            ->post(route('importacoes.periodicidade.store'), [
                'arquivo' => UploadedFile::fake()->image('foto.png'),
            ])
            ->assertRedirect(route('importacoes.periodicidade.create'))
            ->assertSessionHasErrors([
                'arquivo' => 'O campo arquivo CSV deve ser um arquivo do tipo: csv, txt.',
            ]);
    }

    public function test_login_com_email_invalido_exibe_mensagem_em_portugues(): void
    {
        $this->from(route('login'))
            ->post(route('login.submit'), ['email' => 'abc', 'password' => 'x'])
            ->assertSessionHasErrors([
                'email' => 'O campo e-mail deve ser um endereço de e-mail válido.',
            ]);
    }

    public function test_excesso_de_tentativas_exibe_pagina_em_portugues(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.submit'), ['email' => 'a@b.test', 'password' => 'senha-errada']);
        }

        $this->post(route('login.submit'), ['email' => 'a@b.test', 'password' => 'senha-errada'])
            ->assertStatus(429)
            ->assertSee('Muitas tentativas');
    }

    public function test_paginacao_exibe_textos_em_portugues(): void
    {
        for ($i = 1; $i <= 16; $i++) {
            Frota::create(['codigo' => 'F' . $i, 'descricao' => 'Frota de teste ' . $i, 'ativa' => true]);
        }

        $this->actingAs(User::factory()->create(['perfil' => 'gestor']))
            ->get(route('frotas.index'))
            ->assertOk()
            ->assertSee('Mostrando')
            ->assertSee('resultados')
            ->assertDontSee('Showing');
    }
}
