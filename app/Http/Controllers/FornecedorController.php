<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    public function index()
    {
        return Fornecedor::orderBy('nome')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:30'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        return Fornecedor::create($data);
    }

    public function show(Fornecedor $fornecedor)
    {
        return $fornecedor;
    }

    public function update(Request $request, Fornecedor $fornecedor)
    {
        $data = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:30'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        $fornecedor->update($data);

        return $fornecedor;
    }

    public function destroy(Fornecedor $fornecedor)
    {
        $fornecedor->delete();

        return response()->noContent();
    }
}
