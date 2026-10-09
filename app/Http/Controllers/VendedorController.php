<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VendedorController extends Controller
{
    public function index()
    {
        return Vendedor::with('user:id')->orderBy('nome')->get()->map(fn ($v) => $this->present($v));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            // O e-mail só precisa ser único entre usuários quando o vendedor
            // vai ganhar login (senha informada).
            'email' => array_filter(['nullable', 'email', 'max:255', 'required_with:senha', $request->filled('senha') ? Rule::unique('users', 'email') : null]),
            'senha' => ['nullable', 'string', 'min:6', 'max:100'],
            'comissao' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vale_semanal' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'meta_semanal' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'bonus_meta' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        $vendedor = DB::transaction(function () use ($data, $request) {
            $vendedor = Vendedor::create([
                'nome' => $data['nome'],
                'email' => $data['email'] ?? null,
                'comissao' => $data['comissao'] ?? 0,
                'vale_semanal' => $data['vale_semanal'] ?? 0,
                'meta_semanal' => $data['meta_semanal'] ?? 0,
                'bonus_meta' => $data['bonus_meta'] ?? 0,
                'status' => $data['status'] ?? 'ativo',
            ]);

            if (! empty($data['senha'])) {
                $this->sincronizarLogin($vendedor, $request->user()->empresa_id, $data['senha']);
            }

            return $vendedor;
        });

        return response()->json($this->present($vendedor->load('user:id')), 201);
    }

    public function show(Vendedor $vendedor)
    {
        return $this->present($vendedor->load('user:id'));
    }

    public function update(Request $request, Vendedor $vendedor)
    {
        $data = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => array_filter([
                'nullable', 'email', 'max:255',
                ($vendedor->user_id || $request->filled('senha')) ? Rule::unique('users', 'email')->ignore($vendedor->user_id) : null,
            ]),
            // Em branco ao editar = mantém a senha atual.
            'senha' => ['nullable', 'string', 'min:6', 'max:100'],
            'comissao' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vale_semanal' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'meta_semanal' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'bonus_meta' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        // O e-mail é o login: sem ele não dá pra criar nem manter o acesso.
        $temAcesso = $vendedor->user_id || ! empty($data['senha']);
        if ($temAcesso && array_key_exists('email', $data) && empty($data['email'])) {
            return response()->json(['message' => 'O e-mail é obrigatório para quem tem acesso ao app.'], 422);
        }
        if (! $vendedor->user_id && ! empty($data['senha']) && empty($data['email'] ?? $vendedor->email)) {
            return response()->json(['message' => 'Informe o e-mail para criar o acesso do vendedor.'], 422);
        }

        DB::transaction(function () use ($vendedor, $data, $request) {
            $vendedor->update(collect($data)->except('senha')->all());

            if ($vendedor->user_id || ! empty($data['senha'])) {
                $this->sincronizarLogin($vendedor, $request->user()->empresa_id, $data['senha'] ?? null);
            }
        });

        return $this->present($vendedor->fresh('user:id'));
    }

    public function destroy(Vendedor $vendedor)
    {
        DB::transaction(function () use ($vendedor) {
            $user = $vendedor->user;
            $vendedor->delete();
            // Sem o cadastro de vendedor o usuário viraria "admin" (não é mais
            // vendedor) — então o acesso cai junto, junto com os tokens.
            $user?->tokens()->delete();
            $user?->delete();
        });

        return response()->noContent();
    }

    /**
     * Cria ou atualiza o usuário de login do vendedor. Nome, e-mail e status
     * seguem o cadastro do vendedor; a senha só muda quando informada.
     */
    private function sincronizarLogin(Vendedor $vendedor, int $empresaId, ?string $senha): void
    {
        $user = $vendedor->user ?? new User(['empresa_id' => $empresaId, 'cargo' => 'Vendedor']);

        $user->name = $vendedor->nome;
        $user->email = $vendedor->email;
        $user->status = $vendedor->status;
        if ($senha) {
            $user->password = $senha; // cast 'hashed'
        }
        $user->save();

        if ($vendedor->user_id !== $user->id) {
            $vendedor->update(['user_id' => $user->id]);
        }

        // Vendedor inativo ou com senha trocada não pode seguir logado.
        if ($senha || $vendedor->status !== 'ativo') {
            $user->tokens()->delete();
        }
    }

    private function present(Vendedor $vendedor): array
    {
        return [
            'id' => $vendedor->id,
            'nome' => $vendedor->nome,
            'email' => $vendedor->email,
            'comissao' => $vendedor->comissao,
            'valeSemanal' => $vendedor->vale_semanal,
            'metaSemanal' => $vendedor->meta_semanal,
            'bonusMeta' => $vendedor->bonus_meta,
            'status' => $vendedor->status,
            'temAcesso' => $vendedor->user_id !== null,
        ];
    }
}
