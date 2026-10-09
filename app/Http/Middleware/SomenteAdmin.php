<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usuário vinculado a um vendedor só acessa o que o app da vendedora usa
 * (venda, cliente, pedido, consulta de produto). O resto do painel —
 * financeiro, fiscal, configurações — é de quem não é vendedor.
 */
class SomenteAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->ehVendedor()) {
            return response()->json(['message' => 'Você não tem permissão para acessar este recurso.'], 403);
        }

        return $next($request);
    }
}
