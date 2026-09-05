<?php

namespace App\Http\Controllers;

use App\Models\MovimentacaoEstoque;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MovimentacaoEstoqueController extends Controller
{
    public function index()
    {
        return MovimentacaoEstoque::with('produto')->latest()->get()->map(function ($mov) {
            return [
                'id' => $mov->id,
                'produto' => $mov->produto?->nome ?? '—',
                'tipo' => $mov->tipo,
                'quantidade' => (float) $mov->quantidade,
                'motivo' => $mov->motivo,
                'responsavel' => $mov->responsavel,
                'data' => $mov->created_at,
            ];
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'produto_id' => ['required', 'exists:produtos,id'],
            'tipo' => ['required', 'in:entrada,saida,ajuste'],
            'quantidade' => ['required', 'numeric'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $movimentacao = DB::transaction(function () use ($data, $request) {
            $produto = Produto::findOrFail($data['produto_id']);

            $delta = match ($data['tipo']) {
                'entrada' => abs($data['quantidade']),
                'saida' => -abs($data['quantidade']),
                default => $data['quantidade'],
            };

            $produto->increment('estoque', $delta);

            return MovimentacaoEstoque::create([
                'produto_id' => $produto->id,
                'tipo' => $data['tipo'],
                'quantidade' => $delta,
                'motivo' => $data['motivo'] ?? null,
                'responsavel' => $request->user()->name,
            ]);
        });

        return response()->json($movimentacao->load('produto'), 201);
    }
}
