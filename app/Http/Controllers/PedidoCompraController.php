<?php

namespace App\Http\Controllers;

use App\Models\PedidoCompra;
use Illuminate\Http\Request;

class PedidoCompraController extends Controller
{
    public function index()
    {
        return PedidoCompra::latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'cliente_nome' => ['nullable', 'string', 'max:255'],
            'cliente_contato' => ['nullable', 'string', 'max:100'],
            'descricao' => ['required', 'string'],
            'quantidade' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:pendente,comprado,disponivel,concluido,cancelado'],
            'observacoes' => ['nullable', 'string'],
        ]);

        // refresh() traz os defaults do banco (status 'pendente', quantidade 1)
        // que não vieram no request — senão a resposta sai sem esses campos.
        return response()->json(PedidoCompra::create($data)->refresh(), 201);
    }

    public function show(PedidoCompra $pedidoCompra)
    {
        return $pedidoCompra;
    }

    public function update(Request $request, PedidoCompra $pedidoCompra)
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'exists:clientes,id'],
            'cliente_nome' => ['nullable', 'string', 'max:255'],
            'cliente_contato' => ['nullable', 'string', 'max:100'],
            'descricao' => ['sometimes', 'required', 'string'],
            'quantidade' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:pendente,comprado,disponivel,concluido,cancelado'],
            'observacoes' => ['nullable', 'string'],
        ]);

        $pedidoCompra->update($data);

        return $pedidoCompra;
    }

    public function destroy(PedidoCompra $pedidoCompra)
    {
        $pedidoCompra->delete();

        return response()->noContent();
    }
}
