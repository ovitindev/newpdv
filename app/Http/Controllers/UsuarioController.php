<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    public function index()
    {
        return User::where('empresa_id', Auth::user()->empresa_id)
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => $this->present($user));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'cargo' => ['nullable', 'string', 'max:255'],
        ]);

        $senhaTemporaria = Str::random(10);

        $user = User::create([
            'empresa_id' => Auth::user()->empresa_id,
            'name' => $data['nome'],
            'email' => $data['email'],
            'password' => Hash::make($senhaTemporaria),
            'cargo' => $data['cargo'] ?? 'Operador de Caixa',
        ]);

        return response()->json($this->present($user), 201);
    }

    public function update(Request $request, User $usuario)
    {
        abort_unless($usuario->empresa_id === Auth::user()->empresa_id, 404);

        $data = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'cargo' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        if (isset($data['nome'])) {
            $data['name'] = $data['nome'];
            unset($data['nome']);
        }

        $usuario->update($data);

        return $this->present($usuario);
    }

    public function destroy(User $usuario)
    {
        abort_unless($usuario->empresa_id === Auth::user()->empresa_id, 404);
        abort_if($usuario->id === Auth::id(), 422, 'Você não pode remover seu próprio usuário.');

        $usuario->delete();

        return response()->noContent();
    }

    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'nome' => $user->name,
            'email' => $user->email,
            'cargo' => $user->cargo,
            'ultimoAcesso' => $user->updated_at,
            'status' => $user->status,
        ];
    }
}
