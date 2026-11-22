<?php

namespace Tests\Feature;

use App\Models\Rodeiro;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Testes da importação CSV sem afetar o banco MySQL de desenvolvimento.
 * RefreshDatabase usa o banco SQLite em memória do phpunit.xml.
 */
class ImportacaoPeriodicidadeCsvTest extends TestCase
{
    use RefreshDatabase;

    /** Gera um upload de conteúdo CSV para reproduzir o envio pelo navegador. */
    private function csv(string $conteudo): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('periodicidades.csv', $conteudo);
    }

    /** Só o administrador pode consultar a tela ou enviar arquivos. */
    public function test_controle_de_acesso(): void
    {
        $this->get(route('importacoes.periodicidade.create'))
            ->assertRedirect(route('login'));

        foreach (['tecnico', 'gestor'] as $perfil) {
            $usuario = User::factory()->create(['perfil' => $perfil]);
            $this->actingAs($usuario)
                ->get(route('importacoes.periodicidade.create'))->assertForbidden();
            $this->actingAs($usuario)
                ->post(route('importacoes.periodicidade.store'), [
                    'arquivo' => $this->csv("numero_serie;periodicidade\nRD-001;90\n"),
                ])->assertForbidden();
        }
    }

    /** A importação não altera número de série, status ou vínculo do rodeiro. */
    public function test_importa_percentuais_com_virgula_e_ponto(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);
        $primeiro = Rodeiro::create(['numero_serie' => 'RD-001', 'status' => 'instalado']);
        $segundo = Rodeiro::create(['numero_serie' => 'RD-002', 'status' => 'estoque']);

        $this->actingAs($admin)->post(route('importacoes.periodicidade.store'), [
            'arquivo' => $this->csv("numero_serie;periodicidade\nRD-001;95,50\nRD-002;105.25%\n"),
        ])->assertRedirect(route('importacoes.periodicidade.create'))
            ->assertSessionHas('success');

        $this->assertSame('95.50', $primeiro->fresh()->periodicidade);
        $this->assertSame('105.25', $segundo->fresh()->periodicidade);
        $this->assertSame('instalado', $primeiro->fresh()->status);
        $this->assertSame('estoque', $segundo->fresh()->status);
        $this->assertNotNull($primeiro->fresh()->data_atualizacao_condicao);
    }

    /** Arquivos separados por vírgulas também devem ser aceitos. */
    public function test_importa_com_delimitador_virgula_e_cabecalho_invertido(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);
        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-003']);

        $this->actingAs($admin)->post(route('importacoes.periodicidade.store'), [
            'arquivo' => $this->csv("periodicidade,numero_serie\n90.75,RD-003\n"),
        ])->assertSessionHas('success');

        $this->assertSame('90.75', $rodeiro->fresh()->periodicidade);
    }

    /** Uma série inexistente impede todas as alterações do arquivo. */
    public function test_rejeita_serie_desconhecida_sem_atualizar_demais(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);
        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-001', 'periodicidade' => 10]);

        $this->actingAs($admin)->post(route('importacoes.periodicidade.store'), [
            'arquivo' => $this->csv("numero_serie;periodicidade\nRD-001;90\nRD-INEXISTENTE;120\n"),
        ])->assertSessionHasErrors('arquivo');

        $this->assertSame('10.00', $rodeiro->fresh()->periodicidade);
    }

    /** Repetições de série são rejeitadas para não aplicar valores ambíguos. */
    public function test_rejeita_numero_de_serie_repetido(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);
        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-001', 'periodicidade' => 20]);

        $this->actingAs($admin)->post(route('importacoes.periodicidade.store'), [
            'arquivo' => $this->csv("numero_serie;periodicidade\nRD-001;90\nRD-001;110\n"),
        ])->assertSessionHasErrors('arquivo');

        $this->assertSame('20.00', $rodeiro->fresh()->periodicidade);
    }

    /** Percentuais negativos, formatos ambíguos e textos são recusados. */
    public function test_rejeita_percentual_invalido(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);
        $rodeiro = Rodeiro::create(['numero_serie' => 'RD-001', 'periodicidade' => 30]);

        foreach (['abc', '-1', '1000', '99,999'] as $valor) {
            $this->actingAs($admin)->post(route('importacoes.periodicidade.store'), [
                'arquivo' => $this->csv("numero_serie;periodicidade\nRD-001;{$valor}\n"),
            ])->assertSessionHasErrors('arquivo');
        }

        $this->assertSame('30.00', $rodeiro->fresh()->periodicidade);
    }

    /** Cabeçalho incorreto e ausência de registros não alteram o banco. */
    public function test_rejeita_arquivo_sem_cabecalho_ou_sem_dados(): void
    {
        $admin = User::factory()->create(['perfil' => 'administrador']);

        foreach ([
            "serie;percentual\nRD-001;90\n",
            "numero_serie;periodicidade\n",
        ] as $csv) {
            $this->actingAs($admin)->post(route('importacoes.periodicidade.store'), [
                'arquivo' => $this->csv($csv),
            ])->assertSessionHasErrors('arquivo');
        }
    }
}
