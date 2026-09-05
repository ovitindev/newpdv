<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmpresaController extends Controller
{
    public function show()
    {
        return Auth::user()->empresa;
    }

    public function update(Request $request)
    {
        $empresa = Auth::user()->empresa;

        $data = $request->validate([
            'razao_social' => ['sometimes', 'required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['sometimes', 'required', 'string', 'max:20'],
            'inscricao_estadual' => ['nullable', 'string', 'max:50'],
            'regime_tributario' => ['nullable', 'string', 'max:100'],
            'telefone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'uf' => ['nullable', 'string', 'max:2'],
            'cep' => ['nullable', 'string', 'max:15'],
        ]);

        $empresa->update($data);

        return $empresa;
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $empresa = Auth::user()->empresa;

        if ($empresa->logo_path) {
            Storage::disk('public')->delete($empresa->logo_path);
        }

        $path = $request->file('logo')->store("empresas/{$empresa->id}", 'public');
        $empresa->update(['logo_path' => $path]);

        return response()->json([
            'logoUrl' => Storage::disk('public')->url($path),
        ]);
    }
}
