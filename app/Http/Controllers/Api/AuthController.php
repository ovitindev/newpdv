<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            Log::warning('login falhou', ['email' => $data['email'], 'ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas não conferem.'],
            ]);
        }

        if ($user->status !== 'ativo') {
            throw ValidationException::withMessages([
                'email' => ['Este usuário está inativo. Contate o administrador da sua empresa.'],
            ]);
        }

        Log::info('login ok', ['user_id' => $user->id, 'ip' => $request->ip()]);

        $token = $user->createToken('novapdv', ['*'], now()->addDays(7))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
            'empresa' => $user->empresa,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sessão encerrada.']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $this->userPayload($user),
            'empresa' => $user->empresa,
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'nome' => $user->name,
            'email' => $user->email,
            'cargo' => $user->cargo,
            'iniciais' => $user->iniciais(),
            'avatarUrl' => $user->avatar_path,
            // Preenchido = usuário é uma vendedora: usa o app mobile e as
            // vendas saem automaticamente no nome dela.
            'vendedor' => $user->vendedor ? [
                'id' => $user->vendedor->id,
                'nome' => $user->vendedor->nome,
                'comissao' => (float) $user->vendedor->comissao,
            ] : null,
        ];
    }
}
