<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usuário desativado perde o acesso na hora, mesmo com token ainda válido.
 */
class UsuarioAtivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'ativo') {
            $user->tokens()->delete();

            return response()->json(['message' => 'Usuário inativo.'], 401);
        }

        return $next($request);
    }
}
