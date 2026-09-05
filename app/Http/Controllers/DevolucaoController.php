<?php

namespace App\Http\Controllers;

use App\Models\Devolucao;
use Illuminate\Http\Request;

class DevolucaoController extends Controller
{
    public function index()
    {
        return Devolucao::latest()->get()->map(fn ($d) => $this->present($d));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'venda_id' => ['nullable', 'exists:vendas,id'],
            'produto' => ['required', 'string', 'max:255'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0'],
        ]);

        $devolucao = Devolucao::create([...$data, 'status' => 'pendente']);

        return response()->json($this->present($devolucao), 201);
    }

    public function update(Request $request, Devolucao $devolucao)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pendente,aprovada'],
        ]);

        $devolucao->update($data);

        return $this->present($devolucao);
    }

    private function present(Devolucao $devolucao): array
    {
        return [
            'id' => $devolucao->id,
            'venda' => $devolucao->venda_id,
            'produto' => $devolucao->produto,
            'motivo' => $devolucao->motivo,
            'valor' => (float) $devolucao->valor,
            'data' => $devolucao->created_at,
            'status' => $devolucao->status,
        ];
    }
}
