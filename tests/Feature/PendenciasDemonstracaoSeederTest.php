<?php

namespace Tests\Feature;

use App\Models\PendenciaEquipamento;
use App\Models\User;
use Database\Seeders\EquipamentosIniciaisSeeder;
use Database\Seeders\EstruturaFrotasSeeder;
use Database\Seeders\MotivosRetiradaSeeder;
use Database\Seeders\PendenciasDemonstracaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Carga acadêmica em banco de testes isolado: instalações ativas são
 * preservadas e os cenários de criticidade são reproduzíveis.
 */
class PendenciasDemonstracaoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_exatamente_100_abertas_e_20_resolvidas_sem_alterar_instalacoes(): void
    {
        User::factory()->create(['perfil' => 'administrador']);
        $this->seed(EstruturaFrotasSeeder::class);
        $this->seed(EquipamentosIniciaisSeeder::class);
        $this->seed(MotivosRetiradaSeeder::class);

        $rodeirosAntes = DB::table('instalacoes_rodeiros')->whereNull('data_retirada')->count();
        $motoresAntes = DB::table('instalacoes_motores')->whereNull('data_retirada')->count();
        $this->seed(PendenciasDemonstracaoSeeder::class);

        $base = PendenciaEquipamento::where('descricao', 'like', '[SGRM-DEMO-PEND]%');
        $this->assertSame(120, (clone $base)->count());
        $this->assertSame(100, (clone $base)->where('situacao', 'aberta')->count());
        $this->assertSame(20, (clone $base)->where('situacao', 'resolvida')->count());

        foreach ([
            'rodeiro' => ['critica'=>5,'alta'=>10,'media'=>40,'baixa'=>15],
            'motor' => ['critica'=>2,'alta'=>4,'media'=>17,'baixa'=>7],
        ] as $tipo => $esperadas) {
            $fk = $tipo === 'rodeiro' ? 'rodeiro_id' : 'motor_id';
            foreach ($esperadas as $prioridade => $quantidade) {
                $this->assertSame(
                    $quantidade,
                    (clone $base)->whereNotNull($fk)->where('situacao','aberta')
                        ->where('prioridade', $prioridade)->count()
                );
            }
        }

        $this->assertSame($rodeirosAntes, DB::table('instalacoes_rodeiros')->whereNull('data_retirada')->count());
        $this->assertSame($motoresAntes, DB::table('instalacoes_motores')->whereNull('data_retirada')->count());

        $recentes = (clone $base)->where('situacao', 'resolvida')
            ->where('resolvida_em', '>=', now()->startOfDay()->subDays(29))
            ->count();
        $this->assertSame(12, $recentes);

        // A segunda carga deve ser segura e não duplicar pendências.
        $this->seed(PendenciasDemonstracaoSeeder::class);
        $this->assertSame(120, (clone $base)->count());

        // As três frotas recebem pendências abertas dos dois tipos.
        foreach (['I','J','L'] as $frota) {
            foreach (['rodeiro_id','motor_id'] as $fk) {
                $quantidade = DB::table('pendencias_equipamentos as p')
                    ->join('eixos as e','e.id','=','p.eixo_id')
                    ->join('carros as c','c.id','=','e.carro_id')
                    ->join('trens as t','t.id','=','c.trem_id')
                    ->join('frotas as f','f.id','=','t.frota_id')
                    ->where('p.descricao','like','[SGRM-DEMO-PEND]%')
                    ->where('p.situacao','aberta')
                    ->whereNotNull('p.'.$fk)
                    ->where('f.codigo',$frota)->count();
                $this->assertGreaterThan(0, $quantidade);
            }
        }
    }
}
