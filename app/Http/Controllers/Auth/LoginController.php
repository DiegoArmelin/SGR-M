<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Exibe o formulário de login.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Autentica o usuário no sistema.
     */
    public function login(Request $request): RedirectResponse
    {
        // Validação dos dados recebidos.
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Tentativa de autenticação.
        if (Auth::attempt($credentials)) {

            // Regenera a sessão para prevenir
            // ataques de session fixation.
            $request->session()->regenerate();

            // Redireciona para o dashboard.
            return redirect()->intended(
                route('dashboard')
            );
        }

        // Retorna uma mensagem genérica
        // caso as credenciais sejam inválidas.
        return back()
            ->withErrors([
                'email' => 'As credenciais informadas são inválidas.',
            ])
            ->onlyInput('email');
    }

    /**
     * Encerra a sessão do usuário.
     */
    public function logout(Request $request): RedirectResponse
    {
        // Remove a autenticação.
        Auth::logout();

        // Invalida a sessão atual.
        $request->session()->invalidate();

        // Gera um novo token CSRF.
        $request->session()->regenerateToken();

        // Redireciona para o login.
        return redirect()->route('login');
    }
}
