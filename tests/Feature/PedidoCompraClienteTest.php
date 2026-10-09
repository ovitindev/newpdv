<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\PedidoCompra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PedidoCompraClienteTest extends TestCase
{
    use RefreshDatabase;

    private function logar(): User
    {
        $empresa = Empresa::create([
            'razao_social' => 'X', 'nome_fantasia' => 'X', 'cnpj' => '1', 'regime_tributario' => 'Simples Nacional',
        ]);
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_pedido_com_cliente_existente(): void
    {
        $this->logar();
        $cliente = Cliente::create(['nome' => 'Maria', 'telefone' => '119', 'status' => 'ativo']);

        $this->postJson('/api/pedidos-compra', ['cliente_id' => $cliente->id, 'descricao' => 'Tênis 42'])
            ->assertCreated()
            ->assertJsonPath('cliente.nome', 'Maria')
            ->assertJsonPath('status', 'pendente');
    }

    public function test_pedido_cadastrando_cliente_novo(): void
    {
        $this->logar();

        $this->postJson('/api/pedidos-compra', [
            'novo_cliente' => ['nome' => 'João', 'telefone' => '1188'],
            'descricao' => 'Bota',
        ])->assertCreated()->assertJsonPath('cliente.nome', 'João');

        $this->assertDatabaseHas('clientes', ['nome' => 'João', 'telefone' => '1188']);
    }

    public function test_pedido_invalido_nao_deixa_cliente_orfao(): void
    {
        $this->logar();

        $this->postJson('/api/pedidos-compra', ['novo_cliente' => ['nome' => 'Fantasma']])
            ->assertUnprocessable();

        $this->assertSame(0, Cliente::count());
        $this->assertSame(0, PedidoCompra::count());
    }
}
