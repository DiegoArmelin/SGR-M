<?php

namespace Tests\Feature;

use App\Models\InstalacaoMotor;
use App\Models\InstalacaoRodeiro;
use App\Models\Motor;
use App\Models\Rodeiro;
use Database\Seeders\EquipamentosIniciaisSeeder;
use Database\Seeders\EstruturaFrotasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipamentosIniciaisSeederTest extends TestCase
{
    use RefreshDatabase;

    /** O carregamento deve gerar uma instalação ativa de cada tipo por eixo. */
    public function test_gera_equipamentos_instalados_e_sobressalentes(): void
    {
        $this->seed(EstruturaFrotasSeeder::class);
        $this->seed(EquipamentosIniciaisSeeder::class);

        $this->assertSame(1802, Rodeiro::count());
        $this->assertSame(1802, Motor::count());
        $this->assertSame(1752, Rodeiro::where('status', 'instalado')->count());
        $this->assertSame(1752, Motor::where('status', 'instalado')->count());
        $this->assertSame(50, Rodeiro::where('status', 'estoque')->count());
        $this->assertSame(50, Motor::where('status', 'estoque')->count());
        $this->assertSame(1752, InstalacaoRodeiro::whereNull('data_retirada')->count());
        $this->assertSame(1752, InstalacaoMotor::whereNull('data_retirada')->count());
    }

    /** Segunda execução não deve duplicar séries nem instalações. */
    public function test_pode_executar_duas_vezes_sem_duplicacao(): void
    {
        $this->seed(EstruturaFrotasSeeder::class);
        $this->seed(EquipamentosIniciaisSeeder::class);
        $this->seed(EquipamentosIniciaisSeeder::class);

        $this->assertSame(1802, Rodeiro::count());
        $this->assertSame(1802, Motor::count());
        $this->assertSame(1752, InstalacaoRodeiro::count());
        $this->assertSame(1752, InstalacaoMotor::count());
    }
}
