<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProdutoController extends Controller
{
    public function index()
    {
        return Produto::with(['categoria', 'marca'])->orderBy('nome')->get()->map(fn ($produto) => $this->present($produto));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:100'],
            'nome' => ['required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'preco' => ['required', 'numeric', 'min:0'],
            'estoque' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        $produto = Produto::create([
            'codigo' => $data['codigo'],
            'nome' => $data['nome'],
            'categoria_id' => $this->resolveCategoriaId($data['categoria'] ?? null),
            'marca_id' => $this->resolveMarcaId($data['marca'] ?? null),
            'preco' => $data['preco'],
            'estoque' => $data['estoque'] ?? 0,
            'status' => $data['status'] ?? 'ativo',
        ]);

        return $this->present($produto->load(['categoria', 'marca']));
    }

    public function show(Produto $produto)
    {
        return $this->present($produto->load(['categoria', 'marca']));
    }

    public function update(Request $request, Produto $produto)
    {
        $data = $request->validate([
            'codigo' => ['sometimes', 'required', 'string', 'max:100'],
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'preco' => ['sometimes', 'required', 'numeric', 'min:0'],
            'estoque' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['nullable', 'in:ativo,inativo'],
        ]);

        if (array_key_exists('categoria', $data)) {
            $data['categoria_id'] = $this->resolveCategoriaId($data['categoria']);
        }
        if (array_key_exists('marca', $data)) {
            $data['marca_id'] = $this->resolveMarcaId($data['marca']);
        }
        unset($data['categoria'], $data['marca']);

        $produto->update($data);

        return $this->present($produto->load(['categoria', 'marca']));
    }

    public function destroy(Produto $produto)
    {
        $produto->delete();

        return response()->noContent();
    }

    private function resolveCategoriaId(?string $nome): ?int
    {
        if (! $nome) {
            return null;
        }

        return Categoria::firstOrCreate(['empresa_id' => Auth::user()->empresa_id, 'nome' => $nome])->id;
    }

    private function resolveMarcaId(?string $nome): ?int
    {
        if (! $nome) {
            return null;
        }

        return Marca::firstOrCreate(['empresa_id' => Auth::user()->empresa_id, 'nome' => $nome])->id;
    }

    private function present(Produto $produto): array
    {
        return [
            'id' => $produto->id,
            'codigo' => $produto->codigo,
            'nome' => $produto->nome,
            'categoria' => $produto->categoria?->nome ?? '—',
            'marca' => $produto->marca?->nome ?? '—',
            'preco' => (float) $produto->preco,
            'estoque' => (float) $produto->estoque,
            'status' => $produto->status,
        ];
    }
}
