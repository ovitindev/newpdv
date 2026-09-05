<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CertificadoController extends Controller
{
    public function show()
    {
        $certificado = Certificado::firstOrNew(['empresa_id' => Auth::user()->empresa_id]);

        return [
            'arquivo' => $certificado->arquivo_nome,
            'titular' => $certificado->titular,
            'emissor' => $certificado->emissor,
            'validoAte' => $certificado->valido_ate,
            'status' => $certificado->status ?? 'pendente',
        ];
    }

    /**
     * Recebe o .pfx/.p12, valida a senha abrindo o certificado de verdade via
     * OpenSSL e extrai titular/emissor/validade reais — nada disso é simulado.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'arquivo' => ['required', 'file', 'max:5120'],
            'senha' => ['required', 'string'],
        ]);

        $conteudo = file_get_contents($data['arquivo']->getRealPath());

        if (! openssl_pkcs12_read($conteudo, $certInfo, $data['senha'])) {
            return response()->json([
                'message' => 'Não foi possível abrir o certificado. Verifique se o arquivo e a senha estão corretos.',
            ], 422);
        }

        $certData = openssl_x509_parse($certInfo['cert']);
        $titular = $certData['subject']['CN'] ?? $certData['name'] ?? 'Não identificado';
        $emissor = $certData['issuer']['CN'] ?? null;
        $validoAte = isset($certData['validTo_time_t']) ? date('Y-m-d', $certData['validTo_time_t']) : null;

        if ($validoAte && strtotime($validoAte) < time()) {
            return response()->json(['message' => 'Este certificado está vencido.'], 422);
        }

        $empresaId = Auth::user()->empresa_id;
        $path = $data['arquivo']->storeAs("certificados/{$empresaId}", 'certificado.pfx', 'local');

        $certificado = Certificado::updateOrCreate(
            ['empresa_id' => $empresaId],
            [
                'arquivo_nome' => $data['arquivo']->getClientOriginalName(),
                'arquivo_path' => $path,
                'senha' => $data['senha'],
                'titular' => $titular,
                'emissor' => $emissor,
                'valido_ate' => $validoAte,
                'status' => 'valido',
            ]
        );

        return response()->json([
            'arquivo' => $certificado->arquivo_nome,
            'titular' => $certificado->titular,
            'emissor' => $certificado->emissor,
            'validoAte' => $certificado->valido_ate,
            'status' => $certificado->status,
        ], 201);
    }

    public function destroy()
    {
        $certificado = Certificado::where('empresa_id', Auth::user()->empresa_id)->first();

        if ($certificado) {
            if ($certificado->arquivo_path) {
                Storage::disk('local')->delete($certificado->arquivo_path);
            }
            $certificado->delete();
        }

        return response()->noContent();
    }
}
