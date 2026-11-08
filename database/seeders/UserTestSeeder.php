<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserTestSeeder extends Seeder
{
    /**
     * Cria usuários fictícios para testes locais.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'Este seeder não pode ser executado em produção.'
            );
        }

        $senha = env('SGRM_TEST_PASSWORD');

        if (!is_string($senha) || strlen($senha) < 12) {
            throw new RuntimeException(
                'Defina SGRM_TEST_PASSWORD no .env com pelo menos 12 caracteres.'
            );
        }

        $usuarios = [
            [
                'name' => 'Gestor Teste',
                'email' => 'gestor@sgr-m.test',
                'perfil' => User::PERFIL_GESTOR,
            ],
            [
                'name' => 'Tecnico Teste',
                'email' => 'tecnico@sgr-m.test',
                'perfil' => User::PERFIL_TECNICO,
            ],
        ];

        foreach ($usuarios as $dados) {
            $usuario = User::firstOrCreate(
                ['email' => $dados['email']],
                [
                    'name' => $dados['name'],
                    'perfil' => $dados['perfil'],
                    'password' => Hash::make($senha),
                ]
            );

            $this->command->info(
                "Usuário {$usuario->email}: " .
                ($usuario->wasRecentlyCreated ? 'criado' : 'já existente')
            );
        }
    }
}
