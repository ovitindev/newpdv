<?php

namespace App\Http\Controllers;

use App\Models\ConfigFiscal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConfigFiscalController extends Controller
{
    public function show()
    {
        return ConfigFiscal::firstOrCreate(['empresa_id' => Auth::user()->empresa_id]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'ambiente' => ['required', 'in:producao,homologacao'],
            'serie_nfce' => ['required', 'string', 'max:10'],
            'proximo_numero_nfce' => ['required', 'integer', 'min:1'],
            'serie_nfe' => ['required', 'string', 'max:10'],
            'proximo_numero_nfe' => ['required', 'integer', 'min:1'],
            'csc' => ['nullable', 'string'],
            'id_csc' => ['nullable', 'string', 'max:10'],
        ]);

        $config = ConfigFiscal::firstOrCreate(['empresa_id' => Auth::user()->empresa_id]);
        $config->update($data);

        return $config;
    }
}
