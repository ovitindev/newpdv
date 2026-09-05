<?php

namespace App\Http\Controllers;

use App\Models\ConfigFiscal;
use App\Models\NotaFiscal;
use App\Models\Venda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotaFiscalController extends Controller
{
    public function index()
    {
        return NotaFiscal::latest('data')->get();
    }

    /**
     * Simula a emissão de uma NFC-e/NF-e vinculada a uma venda.
     * Quando a integração NFePHP + certificado A1 estiver pronta, este método
     * passa a montar o XML, assinar e transmitir para a SEFAZ de verdade.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'venda_id' => ['required', 'exists:vendas,id'],
            'tipo' => ['required', 'in:NFC-e,NF-e'],
        ]);

        $venda = Venda::with('cliente')->findOrFail($data['venda_id']);

        $nota = DB::transaction(function () use ($data, $venda) {
            $config = ConfigFiscal::firstOrCreate(['empresa_id' => Auth::user()->empresa_id]);

            $isNfce = $data['tipo'] === 'NFC-e';
            $numero = $isNfce ? $config->proximo_numero_nfce : $config->proximo_numero_nfe;
            $serie = $isNfce ? $config->serie_nfce : $config->serie_nfe;

            $config->update([
                $isNfce ? 'proximo_numero_nfce' : 'proximo_numero_nfe' => $numero + 1,
            ]);

            return NotaFiscal::create([
                'venda_id' => $venda->id,
                'numero' => str_pad((string) $numero, 9, '0', STR_PAD_LEFT),
                'serie' => $serie,
                'tipo' => $data['tipo'],
                'cliente_nome' => $venda->cliente?->nome ?? 'Consumidor Final',
                'valor' => $venda->total,
                'status' => 'autorizada',
                'chave_acesso' => collect(range(1, 44))->map(fn () => random_int(0, 9))->implode(''),
                'data' => now(),
            ]);
        });

        return response()->json($nota, 201);
    }

    public function update(Request $request, NotaFiscal $notaFiscal)
    {
        $data = $request->validate([
            'status' => ['required', 'in:autorizada,cancelada,rejeitada'],
        ]);

        $notaFiscal->update($data);

        return $notaFiscal;
    }
}
