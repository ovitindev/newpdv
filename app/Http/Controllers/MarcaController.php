<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    public function index()
    {
        return Marca::withCount('produtos')->orderBy('nome')->get()->map(function ($marca) {
            return [
                'id' => $marca->id,
                'nome' => $marca->nome,
                'produtos' => $marca->produtos_count,
                'status' => $marca->status,
            ];
        });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        return Marca::create($data);
    }

    public function show(Marca $marca)
    {
        return $marca;
    }

    public function update(Request $request, Marca $marca)
    {
        $data = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        $marca->update($data);

        return $marca;
    }

    public function destroy(Marca $marca)
    {
        $marca->delete();

        return response()->noContent();
    }
}
