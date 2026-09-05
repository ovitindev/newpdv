<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    public function index()
    {
        return Categoria::withCount('produtos')->orderBy('nome')->get()->map(function ($categoria) {
            return [
                'id' => $categoria->id,
                'nome' => $categoria->nome,
                'produtos' => $categoria->produtos_count,
                'status' => $categoria->status,
            ];
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        return Categoria::create($data);
    }

    public function show(Categoria $categoria)
    {
        return $categoria;
    }

    public function update(Request $request, Categoria $categoria)
    {
        $data = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        $categoria->update($data);

        return $categoria;
    }

    public function destroy(Categoria $categoria)
    {
        $categoria->delete();

        return response()->noContent();
    }
}
