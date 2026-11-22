<?php

namespace Tests\Feature;

use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\Rodeiro;
use Database\Seeders\EstruturaFrotasSeeder;
use Database\Seeders\HistoricoSubstituicoesSeeder;
use Database\Seeders\MotivosRetiradaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Valida o volume e a reprodutibilidade dos dados exclusivamente fictícios.
 * O teste utiliza SQLite de teste e nunca acessa o MySQL de desenvolvimento.
 */
class HistoricoSubstituicoesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_24_meses_e_preserva_equipamentos_instalados(): void
    {
        $this->seed(EstruturaFrotasSeeder::class);
        $this->seed(MotivosRetiradaSeeder::class);

        // Instalações de produção/demo existentes não devem ser modificadas.
        $rodeiroAtual = Rodeiro::create(['numero_serie' => 'RD-ATUAL', 'status' => 'instalado']);
        $motorAtual = Motor::create(['numero_serie' => 'MT-ATUAL', 'status' => 'instalado']);

        $this->seed(HistoricoSubstituicoesSeeder::class);

        $this->assertSame(600, Rodeiro::where('numero_serie', 'like', 'RD-HIST-%')->count());
        $this->assertSame(480, Motor::where('numero_serie', 'like', 'MT-HIST-%')->count());
        $this->assertSame(600, InstalacaoRodeiro::whereNotNull('data_retirada')->count());
        $this->assertSame(480, InstalacaoMotor::whereNotNull('data_retirada')->count());
        $this->assertSame('instalado', $rodeiroAtual->fresh()->status);
        $this->assertSame('instalado', $motorAtual->fresh()->status);

        // Cada mês possui exatamente 25 rodeiros e 20 motores substituídos.
        $rodeirosPorMes = DB::table('instalacoes_rodeiros')
            ->selectRaw('SUBSTR(data_retirada,1,7) as mes, COUNT(*) as total')
            ->groupByRaw('SUBSTR(data_retirada,1,7)')->get();
        $motoresPorMes = DB::table('instalacoes_motores')
            ->selectRaw('SUBSTR(data_retirada,1,7) as mes, COUNT(*) as total')
            ->groupByRaw('SUBSTR(data_retirada,1,7)')->get();

        $this->assertCount(24, $rodeirosPorMes);
        $this->assertCount(24, $motoresPorMes);
        foreach ($rodeirosPorMes as $item) {
            $this->assertSame(25, (int) $item->total);
        }
        foreach ($motoresPorMes as $item) {
            $this->assertSame(20, (int) $item->total);
        }

        // Uma segunda execução não cria novas instalações ou equipamentos.
        $this->seed(HistoricoSubstituicoesSeeder::class);
        $this->assertSame(600, InstalacaoRodeiro::whereNotNull('data_retirada')->count());
        $this->assertSame(480, InstalacaoMotor::whereNotNull('data_retirada')->count());
    }
}
