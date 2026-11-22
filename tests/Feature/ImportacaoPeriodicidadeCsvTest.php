<?php

namespace Tests\Feature;

use App\Models\Carro;
use App\Models\Eixo;
use App\Models\Frota;
use App\Models\InstalacaoRodeiro;
use App\Models\Rodeiro;
use App\Models\Trem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Verifica a importação das condições por frota/trem/carro/eixo e série.
 * Os testes não tocam no MySQL local: utilizam o banco de testes isolado.
 */
class ImportacaoPeriodicidadeCsvTest extends TestCase
{
    use RefreshDatabase;

    private const CABECALHO = 'frota;trem;carro;posicao;serie_rodeiro;diametro_lr;diametro_lo;periodicidade;data_atualizacao';

    private function csv(string $linhas): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            'condicoes.csv', self::CABECALHO . "\n" . $linhas
        );
    }

    /** Simula um carro com dois eixos e rodeiros em instalação ativa. */
    private function preparar(): array
    {
        $frota = Frota::create(['codigo' => 'I']);
        $trem = Trem::create(['frota_id' => $frota->id, 'codigo' => 'I01']);
        $carro = Carro::create(['trem_id' => $trem->id, 'codigo' => 'I011', 'posicao' => 1]);
        $primeiro = Rodeiro::create(['numero_serie' => 'RD-001', 'status' => 'instalado']);
        $segundo = Rodeiro::create(['numero_serie' => 'RD-002', 'status' => 'instalado']);

        foreach ([1 => $primeiro, 2 => $segundo] as $posicao => $rodeiro) {
            $eixo = Eixo::create(['carro_id' => $carro->id, 'numero' => $posicao]);
            InstalacaoRodeiro::create([
                'eixo_id' => $eixo->id,
                'rodeiro_id' => $rodeiro->id,
                'data_instalacao' => '2026-01-01',
            ]);
        }

        return compact('primeiro', 'segundo');
    }

    private function administrador(): User
    {
        return User::factory()->create(['perfil' => 'administrador']);
    }

    public function test_apenas_administrador_pode_importar(): void
    {
        $this->get(route('importacoes.periodicidade.create'))
            ->assertRedirect(route('login'));

        foreach (['tecnico', 'gestor'] as $perfil) {
            $usuario = User::factory()->create(['perfil' => $perfil]);
            $this->actingAs($usuario)
                ->get(route('importacoes.periodicidade.create'))->assertForbidden();
            $this->actingAs($usuario)
                ->post(route('importacoes.periodicidade.store'), [
                    'arquivo' => $this->csv("I;I01;I011;1;RD-001;825.1;825.2;95;2026-11-21 12:00:00\n"),
                ])->assertForbidden();
        }
    }

    public function test_atualiza_periodicidade_e_ambos_diametros_em_um_unico_lote(): void
    {
        $r = $this->preparar();
        $this->actingAs($this->administrador())->post(route('importacoes.periodicidade.store'), [
            'arquivo' => $this->csv(
                "I;I01;I011;1;RD-001;825,40;825,85;95,50;2026-11-21 12:00:00\n"
                . "I;I01;I011;2;RD-002;823.20;823.70;110.25;2026-11-21 12:00:00\n"
            ),
        ])->assertRedirect(route('importacoes.periodicidade.create'))
          ->assertSessionHas('success');

        $this->assertSame('825.40', $r['primeiro']->fresh()->diametro_lr);
        $this->assertSame('825.85', $r['primeiro']->fresh()->diametro_lo);
        $this->assertSame('95.50', $r['primeiro']->fresh()->periodicidade);
        $this->assertSame('823.20', $r['segundo']->fresh()->diametro_lr);
        $this->assertSame('110.25', $r['segundo']->fresh()->periodicidade);
        $this->assertSame('2026-11-21 12:00:00',
            $r['primeiro']->fresh()->data_atualizacao_condicao->format('Y-m-d H:i:s'));
    }

    public function test_ignora_medicao_anterior_ou_igual_sem_alterar_valores(): void
    {
        $r = $this->preparar();
        $r['primeiro']->update([
            'diametro_lr' => 810,
            'diametro_lo' => 811,
            'periodicidade' => 88,
            'data_atualizacao_condicao' => '2026-11-21 12:00:00',
        ]);
        $this->actingAs($this->administrador())->post(route('importacoes.periodicidade.store'), [
            'arquivo' => $this->csv(
                "I;I01;I011;1;RD-001;740;740;120;2026-11-21 12:00:00\n"
                . "I;I01;I011;2;RD-002;828;829;90;2026-11-21 12:00:00\n"
            ),
        ])->assertSessionHas('success');

        $this->assertSame('810.00', $r['primeiro']->fresh()->diametro_lr);
        $this->assertSame('88.00', $r['primeiro']->fresh()->periodicidade);
        $this->assertSame('828.00', $r['segundo']->fresh()->diametro_lr);
    }

    public function test_nova_medicao_substitui_a_condicao_antiga(): void
    {
        $r = $this->preparar();
        $r['primeiro']->update([
            'periodicidade' => 70,
            'data_atualizacao_condicao' => '2026-11-20 10:00:00',
        ]);

        $this->actingAs($this->administrador())->post(route('importacoes.periodicidade.store'), [
            'arquivo' => $this->csv(
                "I;I01;I011;1;RD-001;801;802;110;2026-11-21 12:00:00\n"
            ),
        ])->assertSessionHas('success');

        $this->assertSame('110.00', $r['primeiro']->fresh()->periodicidade);
    }

    public function test_ignora_serie_que_ja_nao_esta_na_posicao(): void
    {
        $r = $this->preparar();
        // A série RD-002 ainda existe, mas pertence ao eixo 2, não ao 1.
        $this->actingAs($this->administrador())->post(route('importacoes.periodicidade.store'), [
            'arquivo' => $this->csv(
                "I;I01;I011;1;RD-002;750;750;115;2026-11-21 12:00:00\n"
            ),
        ])->assertSessionHas('success');

        $this->assertNull($r['segundo']->fresh()->diametro_lr);
    }

    public function test_erro_de_serie_ou_posicao_reverte_o_lote(): void
    {
        $r = $this->preparar();
        $this->actingAs($this->administrador())->post(route('importacoes.periodicidade.store'), [
            'arquivo' => $this->csv(
                "I;I01;I011;1;RD-001;800;801;90;2026-11-21 12:00:00\n"
                . "I;I01;I011;99;RD-002;800;801;90;2026-11-21 12:00:00\n"
            ),
        ])->assertSessionHasErrors('arquivo');

        $this->assertNull($r['primeiro']->fresh()->periodicidade);
    }

    public function test_serie_desconhecida_e_rejeitada_antes_de_gravar(): void
    {
        $r = $this->preparar();
        $this->actingAs($this->administrador())->post(route('importacoes.periodicidade.store'), [
            'arquivo' => $this->csv(
                "I;I01;I011;1;RD-001;800;801;90;2026-11-21 12:00:00\n"
                . "I;I01;I011;2;RD-DESCONHECIDO;800;801;90;2026-11-21 12:00:00\n"
            ),
        ])->assertSessionHasErrors('arquivo');

        $this->assertNull($r['primeiro']->fresh()->periodicidade);
    }

    public function test_dados_invalidos_ou_duplicados_sao_rejeitados(): void
    {
        $r = $this->preparar();
        $admin = $this->administrador();

        foreach ([
            "I;I01;I011;1;RD-001;abc;820;90;2026-11-21 12:00:00\n",
            "I;I01;I011;1;RD-001;820;820;90;2026-02-30 12:00:00\n",
            "I;I01;I011;1;RD-001;820;820;90;2026-11-21 12:00:00\n"
                . "I;I01;I011;2;RD-001;820;820;90;2026-11-21 12:00:00\n",
        ] as $conteudo) {
            $this->actingAs($admin)->post(route('importacoes.periodicidade.store'), [
                'arquivo' => $this->csv($conteudo),
            ])->assertSessionHasErrors('arquivo');
        }

        $this->assertNull($r['primeiro']->fresh()->periodicidade);
    }
}
