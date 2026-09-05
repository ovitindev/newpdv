<?php

namespace App\Http\Controllers;

use App\Models\Vendedor;
use Illuminate\Http\Request;

class VendedorController extends Controller
{
    public function index()
    {
        return Vendedor::orderBy('nome')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'comissao' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        $data['status'] = $data['status'] ?? 'ativo';

        return Vendedor::create($data);
    }

    public function show(Vendedor $vendedor)
    {
        return $vendedor;
    }

    public function update(Request $request, Vendedor $vendedor)
    {
        $data = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'comissao' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        $vendedor->update($data);

        return $vendedor;
    }

    public function destroy(Vendedor $vendedor)
    {
        $vendedor->delete();

        return response()->noContent();
    }
}
