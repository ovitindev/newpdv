<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConfigFiscal;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'razao_social' => ['required', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'max:20', 'unique:empresas,cnpj'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $empresa = Empresa::create([
                'razao_social' => $data['razao_social'],
                'nome_fantasia' => $data['nome_fantasia'] ?? $data['razao_social'],
                'cnpj' => $data['cnpj'],
            ]);

            ConfigFiscal::create(['empresa_id' => $empresa->id]);

            $user = User::create([
                'empresa_id' => $empresa->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'cargo' => 'Administrador',
            ]);

            return [$empresa, $user];
        });

        [$empresa, $user] = $result;

        $token = $user->createToken('novapdv')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
            'empresa' => $empresa,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas não conferem.'],
            ]);
        }

        if ($user->status !== 'ativo') {
            throw ValidationException::withMessages([
                'email' => ['Este usuário está inativo. Contate o administrador da sua empresa.'],
            ]);
        }

        $token = $user->createToken('novapdv')->plainTextToken;

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
        ];
    }
}
