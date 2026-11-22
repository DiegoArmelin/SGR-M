<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ConsultaEquipamentosController;
use App\Http\Controllers\ConsultaHierarquicaController;
use App\Http\Controllers\FrotaController;
use App\Http\Controllers\TremController;
use App\Http\Controllers\MovimentacaoEquipamentoController;
use App\Http\Controllers\PendenciaEquipamentoController;

/*
|--------------------------------------------------------------------------
| Rotas públicas
|--------------------------------------------------------------------------
*/

// Redireciona a página inicial para o login
// ou para o dashboard, se já estiver autenticado.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Rotas de autenticação
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    // Exibir formulário de login.
    Route::get('/login', [
        LoginController::class,
        'showLoginForm'
    ])->name('login');

    // Processar autenticação.
    Route::post('/login', [
        LoginController::class,
        'login'
    ])->middleware('throttle:5,1')
      ->name('login.submit');

});

/*
|--------------------------------------------------------------------------
| Rotas protegidas
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Dashboard principal.
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Consulta permitida aos três perfis autenticados.
    Route::get('/equipamentos', [ConsultaEquipamentosController::class, 'index'])
        ->middleware('perfil:administrador,gestor,tecnico')
        ->name('equipamentos.index');

    // Consulta hierárquica somente leitura, acessível aos três perfis.
    Route::get('/equipamentos/hierarquica', [ConsultaHierarquicaController::class, 'index'])
        ->middleware('perfil:administrador,gestor,tecnico')
        ->name('equipamentos.hierarquica');

    // Histórico de instalações: consulta permitida a todos os perfis.
    Route::get('/eixos/{eixo}/historico', [MovimentacaoEquipamentoController::class, 'historico'])
        ->middleware('perfil:administrador,gestor,tecnico')
        ->name('equipamentos.historico');

    // Movimentação: apenas administrador ou técnico pode efetuar substituições.
    Route::middleware('perfil:administrador,tecnico')->group(function () {
        Route::get('/eixos/{eixo}/substituir/{tipo}', [MovimentacaoEquipamentoController::class, 'create'])
            ->name('equipamentos.movimentacao.create');
        Route::post('/eixos/{eixo}/substituir/{tipo}', [MovimentacaoEquipamentoController::class, 'store'])
            ->name('equipamentos.movimentacao.store');
    });

    // Gestão de pendências: consulta, cadastro e resolução para os três perfis.
    Route::middleware('perfil:administrador,gestor,tecnico')->group(function () {
        Route::get('/pendencias', [PendenciaEquipamentoController::class, 'index'])->name('pendencias.index');
        Route::get('/pendencias/criar', [PendenciaEquipamentoController::class, 'create'])->name('pendencias.create');
        Route::post('/pendencias', [PendenciaEquipamentoController::class, 'store'])->name('pendencias.store');
    });

    // Consulta de frotas e trens: todos os perfis autenticados.
    Route::middleware('perfil:administrador,gestor,tecnico')->group(function () {
        Route::get('/frotas', [FrotaController::class, 'index'])->name('frotas.index');
        Route::get('/trens', [TremController::class, 'index'])->name('trens.index');
    });

    // Cadastros: somente administrador, com rotas estáticas antes das parametrizadas.
    Route::middleware('perfil:administrador')->group(function () {
        Route::get('/frotas/criar', [FrotaController::class, 'create'])->name('frotas.create');
        Route::post('/frotas', [FrotaController::class, 'store'])->name('frotas.store');
        Route::get('/frotas/{frota}/editar', [FrotaController::class, 'edit'])->name('frotas.edit');
        Route::put('/frotas/{frota}', [FrotaController::class, 'update'])->name('frotas.update');
        Route::delete('/frotas/{frota}', [FrotaController::class, 'destroy'])->name('frotas.destroy');
        Route::get('/trens/criar', [TremController::class, 'create'])->name('trens.create');
        Route::post('/trens', [TremController::class, 'store'])->name('trens.store');
        Route::get('/trens/{trem}/editar', [TremController::class, 'edit'])->name('trens.edit');
        Route::put('/trens/{trem}', [TremController::class, 'update'])->name('trens.update');
        Route::delete('/trens/{trem}', [TremController::class, 'destroy'])->name('trens.destroy');
    });

    // Encerrar sessão.
    Route::post('/logout', [
        LoginController::class,
        'logout'
    ])->name('logout');

});
