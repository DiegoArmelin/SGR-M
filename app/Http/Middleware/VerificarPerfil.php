<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPerfil
{
    /**
     * Verifica se o usuário possui um dos perfis autorizados.
     *
     * @param Closure $next
     * @param string ...$perfis
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$perfis
    ): Response {

        // Verifica se existe usuário autenticado.
        $user = $request->user();

        if (!$user) {
            abort(401, 'Usuário não autenticado.');
        }

        // Verifica se o usuário possui um perfil permitido.
        if (!$user->temPerfil(...$perfis)) {
            abort(403, 'Você não possui permissão para acessar esta funcionalidade.');
        }

        return $next($request);
    }
}
