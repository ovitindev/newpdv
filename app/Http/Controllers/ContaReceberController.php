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

        $porDescricao = ContaReceber::where('recorrente', true)
            ->orderBy('vencimento')
            ->get()
            ->groupBy('descricao');

        $criadas = [];

        foreach ($porDescricao as $ocorrencias) {
            $maisRecente = $ocorrencias->last();
            // Usa sempre o dia-do-mês da 1ª ocorrência (não da mais recente,
            // que pode já ter sido clampada por um mês curto) — senão uma
            // conta do dia 31 vira 28 e nunca mais volta a ser 31.
            $diaOriginal = $ocorrencias->first()->vencimento->day;

            $destino = $maisRecente->vencimento->copy()->addMonthNoOverflow();
            $destino->day(min($diaOriginal, $destino->daysInMonth));

            if ($destino->year > $hoje->year || ($destino->year === $hoje->year && $destino->month > $hoje->month)) {
                continue;
            }

            $jaExiste = ContaReceber::where('descricao', $maisRecente->descricao)
                ->whereYear('vencimento', $destino->year)
                ->whereMonth('vencimento', $destino->month)
                ->exists();

            if ($jaExiste) {
                continue;
            }

            $criadas[] = ContaReceber::create([
                'descricao' => $maisRecente->descricao,
                'categoria' => $maisRecente->categoria,
                'valor' => $maisRecente->valor,
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
