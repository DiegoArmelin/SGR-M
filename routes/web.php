<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

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

    // Encerrar sessão.
    Route::post('/logout', [
        LoginController::class,
        'logout'
    ])->name('logout');

});
