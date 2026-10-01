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
            'quantidade' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:pendente,comprado,disponivel,concluido,cancelado'],
            'observacoes' => ['nullable', 'string'],
        ]);

        return response()->json(PedidoCompra::create($data), 201);
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
            'quantidade' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:pendente,comprado,disponivel,concluido,cancelado'],
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
