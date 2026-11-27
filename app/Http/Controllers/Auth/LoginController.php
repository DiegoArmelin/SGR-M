<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Tentativas malsucedidas permitidas por e-mail e IP antes do bloqueio. */
    private const MAX_TENTATIVAS = 5;

    /** Duração do bloqueio, em segundos. */
    private const BLOQUEIO_SEGUNDOS = 60;

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

        // O limite é por e-mail + IP e conta só as falhas: em rede corporativa
        // (NAT/proxy) todos compartilham o IP, e um limite apenas por IP
        // bloquearia o acesso de todos os usuários.
        $chave = $this->chaveLimite($request);

        if (RateLimiter::tooManyAttempts($chave, self::MAX_TENTATIVAS)) {
            event(new Lockout($request));

            $segundos = RateLimiter::availableIn($chave);

            throw ValidationException::withMessages([
                'email' => __('auth.throttle', [
                    'seconds' => $segundos,
                    'minutes' => (int) ceil($segundos / 60),
                ]),
            ]);
        }

        // Tentativa de autenticação.
        if (Auth::attempt($credentials)) {
            RateLimiter::clear($chave);

            // Regenera a sessão para prevenir
            // ataques de session fixation.
            $request->session()->regenerate();

            // Redireciona para o dashboard.
            return redirect()->intended(
                route('dashboard')
            );
        }

        RateLimiter::hit($chave, self::BLOQUEIO_SEGUNDOS);

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

    /** Chave do limitador: e-mail normalizado + IP de origem. */
    private function chaveLimite(Request $request): string
    {
        return 'login:' . Str::transliterate(
            Str::lower((string) $request->input('email')) . '|' . $request->ip()
        );
    }
}
