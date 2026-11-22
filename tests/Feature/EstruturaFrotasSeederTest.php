<?php

namespace Tests\Feature;

use App\Models\Carro;
use App\Models\Eixo;
use App\Models\Frota;
use App\Models\Trem;
use Database\Seeders\EstruturaFrotasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstruturaFrotasSeederTest extends TestCase
{
    use RefreshDatabase;

    /** Verifica quantidade, limites de numeração e relacionamento entre entidades. */
    public function test_seeder_cria_frotas_trens_carros_e_eixos_corretamente(): void
    {
        $this->seed(EstruturaFrotasSeeder::class);

        $this->assertSame(3, Frota::count());
        $this->assertSame(73, Trem::count());
        $this->assertSame(438, Carro::count());
        $this->assertSame(1752, Eixo::count());

        $this->assertSame(25, Trem::whereHas('frota', fn ($q) => $q->where('codigo', 'I'))->count());
        $this->assertSame(26, Trem::whereHas('frota', fn ($q) => $q->where('codigo', 'J'))->count());
        $this->assertSame(22, Trem::whereHas('frota', fn ($q) => $q->where('codigo', 'L'))->count());

        $this->assertDatabaseHas('trens', ['codigo' => 'I01']);
        $this->assertDatabaseHas('trens', ['codigo' => 'I25']);
        $this->assertDatabaseHas('trens', ['codigo' => 'J26']);
        $this->assertDatabaseHas('trens', ['codigo' => 'J51']);
        $this->assertDatabaseHas('trens', ['codigo' => 'L26']);
        $this->assertDatabaseHas('trens', ['codigo' => 'L47']);

        // Confere padrão de identificação I011 até I016.
        $trem = Trem::where('codigo', 'I01')->firstOrFail();
        $this->assertSame(['I011','I012','I013','I014','I015','I016'],
            $trem->carros()->orderBy('posicao')->pluck('codigo')->all());

        // Confere as quatro posições de eixos do primeiro carro.
        $carro = $trem->carros()->where('posicao', 1)->firstOrFail();
        $this->assertSame([1,2,3,4], $carro->eixos()->orderBy('numero')->pluck('numero')->all());
    }

    /** Executar duas vezes não deve criar frotas, trens, carros ou eixos duplicados. */
    public function test_seeder_pode_ser_executado_novamente_sem_duplicar(): void
    {
        $this->seed(EstruturaFrotasSeeder::class);
        $this->seed(EstruturaFrotasSeeder::class);

        $this->assertSame(3, Frota::count());
        $this->assertSame(73, Trem::count());
        $this->assertSame(438, Carro::count());
        $this->assertSame(1752, Eixo::count());
    }
}
