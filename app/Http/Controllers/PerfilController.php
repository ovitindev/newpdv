<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use Illuminate\Http\Request;

class PerfilController extends Controller
{
    public function index()
    {
        return Perfil::orderBy('nome')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'permissoes' => ['nullable', 'array'],
        ]);

        return Perfil::create($data);
    }

    public function show(Perfil $perfil)
    {
        return $perfil;
    }

    public function update(Request $request, Perfil $perfil)
    {
        $data = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'permissoes' => ['nullable', 'array'],
        ]);

        $perfil->update($data);

        return $perfil;
    }

    public function destroy(Perfil $perfil)
    {
        $perfil->delete();

        return response()->noContent();
    }
}
