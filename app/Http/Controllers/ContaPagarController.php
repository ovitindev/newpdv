<?php

namespace App\Http\Controllers;

use App\Models\ContaPagar;
use Illuminate\Http\Request;

class ContaPagarController extends Controller
{
    public function index()
    {
        return ContaPagar::orderBy('vencimento')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'descricao' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0'],
            'vencimento' => ['required', 'date'],
            'status' => ['nullable', 'in:pendente,atrasado,pago'],
        ]);

        return ContaPagar::create($data);
    }

    public function show(ContaPagar $contaPagar)
    {
        return $contaPagar;
    }

    public function update(Request $request, ContaPagar $contaPagar)
    {
        $data = $request->validate([
            'descricao' => ['sometimes', 'required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'valor' => ['sometimes', 'required', 'numeric', 'min:0'],
            'vencimento' => ['sometimes', 'required', 'date'],
            'status' => ['nullable', 'in:pendente,atrasado,pago'],
        ]);

        $contaPagar->update($data);

        return $contaPagar;
    }

    public function destroy(ContaPagar $contaPagar)
    {
        $contaPagar->delete();

        return response()->noContent();
    }
}
