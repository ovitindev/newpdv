<?php

namespace App\Http\Controllers;

use App\Models\ContaReceber;
use Illuminate\Http\Request;

class ContaReceberController extends Controller
{
    public function index()
    {
        return ContaReceber::orderBy('vencimento')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'descricao' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0'],
            'vencimento' => ['required', 'date'],
            'status' => ['nullable', 'in:pendente,atrasado,recebido'],
            'recorrente' => ['nullable', 'boolean'],
        ]);

        return ContaReceber::create($data);
    }

    public function show(ContaReceber $contaReceber)
    {
        return $contaReceber;
    }

    public function update(Request $request, ContaReceber $contaReceber)
    {
        $data = $request->validate([
            'descricao' => ['sometimes', 'required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'valor' => ['sometimes', 'required', 'numeric', 'min:0'],
            'vencimento' => ['sometimes', 'required', 'date'],
            'status' => ['nullable', 'in:pendente,atrasado,recebido'],
            'recorrente' => ['nullable', 'boolean'],
        ]);

        $contaReceber->update($data);

        return $contaReceber;
    }

    public function destroy(ContaReceber $contaReceber)
    {
        $contaReceber->delete();

        return response()->noContent();
    }

    /**
     * Gera a proxima ocorrencia (vencimento + 1 mes) de cada conta marcada
     * como recorrente, usando sempre a instancia mais recente de cada
     * descricao como base. So cria se o mes de destino ja chegou (nao
     * adianta meses futuros em cliques repetidos) e pula se o mes de
     * destino ja tem uma conta com a mesma descricao.
     */
    public function replicar()
    {
        $hoje = now();

        $ultimasPorDescricao = ContaReceber::where('recorrente', true)
            ->orderByDesc('vencimento')
            ->get()
            ->unique('descricao');

        $criadas = [];

        foreach ($ultimasPorDescricao as $conta) {
            $destino = $conta->vencimento->copy()->addMonthNoOverflow();

            if ($destino->year > $hoje->year || ($destino->year === $hoje->year && $destino->month > $hoje->month)) {
                continue;
            }

            $jaExiste = ContaReceber::where('descricao', $conta->descricao)
                ->whereYear('vencimento', $destino->year)
                ->whereMonth('vencimento', $destino->month)
                ->exists();

            if ($jaExiste) {
                continue;
            }

            $criadas[] = ContaReceber::create([
                'descricao' => $conta->descricao,
                'categoria' => $conta->categoria,
                'valor' => $conta->valor,
                'vencimento' => $destino,
                'status' => 'pendente',
                'recorrente' => true,
            ]);
        }

        return response()->json([
            'criadas' => count($criadas),
            'contas' => $criadas,
        ]);
    }
}
