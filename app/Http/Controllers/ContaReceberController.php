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
        ]);

        $contaReceber->update($data);

        return $contaReceber;
    }

    public function destroy(ContaReceber $contaReceber)
    {
        $contaReceber->delete();

        return response()->noContent();
    }
}
