<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcessoVendedorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $empresa = Empresa::create([
            'razao_social' => 'X', 'nome_fantasia' => 'X', 'cnpj' => '1', 'regime_tributario' => 'Simples Nacional',
        ]);
        $this->admin = User::factory()->create(['empresa_id' => $empresa->id]);
    }

    private function criarVendedora(string $email = 'ana@loja.com'): array
    {
        Sanctum::actingAs($this->admin);
        $id = $this->postJson('/api/vendedores', [
            'nome' => 'Ana', 'email' => $email, 'senha' => 'segredo1', 'comissao' => 5,
        ])->assertCreated()->assertJsonPath('temAcesso', true)->json('id');
        app('auth')->forgetGuards();

        return [$id, $email];
    }

    public function test_vendedora_faz_login_com_email_e_senha_do_cadastro(): void
    {
        $this->criarVendedora();

        $this->postJson('/api/login', ['email' => 'ana@loja.com', 'password' => 'segredo1'])
            ->assertOk()
            ->assertJsonPath('user.vendedor.nome', 'Ana')
            ->assertJsonPath('user.vendedor.comissao', 5);
    }

    public function test_venda_sai_no_nome_da_vendedora_mesmo_com_vendedor_id_forjado(): void
    {
        [$ana] = $this->criarVendedora();
        $outra = Vendedor::create(['empresa_id' => $this->admin->empresa_id, 'nome' => 'Outra']);
        $produto = Produto::create(['empresa_id' => $this->admin->empresa_id, 'codigo' => 'A1', 'nome' => 'Tênis', 'preco' => 100, 'estoque' => 5, 'status' => 'ativo']);

        Sanctum::actingAs(User::where('email', 'ana@loja.com')->first());

        $this->postJson('/api/vendas', [
            'vendedor_id' => $outra->id,
            'itens' => [['produto_id' => $produto->id, 'nome' => 'Tênis', 'preco' => 100, 'quantidade' => 1]],
            'pagamentos' => [['metodo' => 'pix', 'valor' => 100]],
        ])->assertCreated()->assertJsonPath('vendedorId', $ana);

        $this->getJson('/api/vendas')->assertOk()->assertJsonCount(1);
    }

    public function test_vendedora_ve_vendas_de_todos_mas_nao_acessa_areas_administrativas(): void
    {
        $this->criarVendedora();
        $outra = Vendedor::create(['empresa_id' => $this->admin->empresa_id, 'nome' => 'Outra']);
        $venda = \App\Models\Venda::create([
            'empresa_id' => $this->admin->empresa_id, 'vendedor_id' => $outra->id,
            'subtotal' => 10, 'total' => 10, 'status' => 'concluida',
        ]);

        Sanctum::actingAs(User::where('email', 'ana@loja.com')->first());

        $this->getJson("/api/vendas/{$venda->id}")->assertOk();
        $this->getJson('/api/vendas')->assertOk()->assertJsonCount(1);
        $this->getJson('/api/vendedores')->assertForbidden();
        $this->getJson('/api/contas-pagar')->assertForbidden();
        $this->getJson('/api/dashboard')->assertForbidden();
        $this->postJson('/api/produtos', [])->assertForbidden();
        $this->getJson('/api/produtos')->assertOk();
        $this->getJson('/api/clientes')->assertOk();
    }

    public function test_vendedor_inativo_ou_removido_perde_o_acesso(): void
    {
        [$id] = $this->criarVendedora();
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/vendedores/{$id}", ['nome' => 'Ana', 'email' => 'ana@loja.com', 'status' => 'inativo'])->assertOk();
        app('auth')->forgetGuards();
        $this->postJson('/api/login', ['email' => 'ana@loja.com', 'password' => 'segredo1'])->assertUnprocessable();

        Sanctum::actingAs($this->admin);
        $this->deleteJson("/api/vendedores/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('users', ['email' => 'ana@loja.com']);
    }

    public function test_editar_sem_senha_mantem_a_senha_e_troca_quando_informada(): void
    {
        [$id] = $this->criarVendedora();
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/vendedores/{$id}", ['nome' => 'Ana Maria', 'email' => 'ana@loja.com'])->assertOk();
        app('auth')->forgetGuards();
        $this->postJson('/api/login', ['email' => 'ana@loja.com', 'password' => 'segredo1'])
            ->assertOk()->assertJsonPath('user.nome', 'Ana Maria');

        Sanctum::actingAs($this->admin);
        $this->putJson("/api/vendedores/{$id}", ['nome' => 'Ana Maria', 'email' => 'ana@loja.com', 'senha' => 'novasenha'])->assertOk();
        app('auth')->forgetGuards();
        $this->postJson('/api/login', ['email' => 'ana@loja.com', 'password' => 'segredo1'])->assertUnprocessable();
        $this->postJson('/api/login', ['email' => 'ana@loja.com', 'password' => 'novasenha'])->assertOk();
    }

    public function test_email_de_login_nao_pode_repetir(): void
    {
        $this->criarVendedora();
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/vendedores', ['nome' => 'Bia', 'email' => 'ana@loja.com', 'senha' => 'segredo2'])
            ->assertUnprocessable();
    }

    public function test_admin_filtra_vendas_pelo_vendedor_escolhido(): void
    {
        $a = Vendedor::create(['empresa_id' => $this->admin->empresa_id, 'nome' => 'A']);
        $b = Vendedor::create(['empresa_id' => $this->admin->empresa_id, 'nome' => 'B']);
        foreach ([$a, $b, $b] as $v) {
            \App\Models\Venda::create(['empresa_id' => $this->admin->empresa_id, 'vendedor_id' => $v->id, 'subtotal' => 10, 'total' => 10, 'status' => 'concluida']);
        }
        Sanctum::actingAs($this->admin);

        $this->getJson("/api/vendas?vendedor_id={$b->id}")->assertOk()->assertJsonCount(2);
        $this->getJson("/api/vendas?vendedor_id={$a->id}")->assertOk()->assertJsonCount(1);
        $this->getJson('/api/vendas')->assertOk()->assertJsonCount(3);
    }

    public function test_reenviar_a_mesma_venda_nao_duplica(): void
    {
        $this->criarVendedora();
        $produto = Produto::create(['empresa_id' => $this->admin->empresa_id, 'codigo' => 'A1', 'nome' => 'Tênis', 'preco' => 100, 'estoque' => 5, 'status' => 'ativo']);
        Sanctum::actingAs(User::where('email', 'ana@loja.com')->first());

        $payload = [
            'client_uuid' => '7f0c3b52-4a6e-4d0b-9a57-0f6a1f1a2b3c',
            'itens' => [['produto_id' => $produto->id, 'nome' => 'Tênis', 'preco' => 100, 'quantidade' => 1]],
            'pagamentos' => [['metodo' => 'pix', 'valor' => 100]],
        ];

        $primeira = $this->postJson('/api/vendas', $payload)->assertCreated()->json('id');
        $segunda = $this->postJson('/api/vendas', $payload)->assertOk()->json('id');

        $this->assertSame($primeira, $segunda);
        $this->assertSame(1, \App\Models\Venda::count());
        $this->assertEquals(4, $produto->fresh()->estoque); // baixou uma vez só
    }

    private function vendaDe(Vendedor $v, float $total = 50): \App\Models\Venda
    {
        $venda = \App\Models\Venda::create([
            'empresa_id' => $this->admin->empresa_id, 'vendedor_id' => $v->id,
            'subtotal' => $total, 'total' => $total, 'status' => 'concluida',
        ]);
        // Venda concluída precisa de item (regra do sistema ao editar).
        \App\Models\VendaItem::create(['venda_id' => $venda->id, 'nome' => 'Item', 'preco' => $total, 'quantidade' => 1]);

        return $venda;
    }

    public function test_vendedora_edita_e_apaga_so_as_proprias_vendas(): void
    {
        [$ana] = $this->criarVendedora();
        $anaVendedor = Vendedor::find($ana);
        $outra = Vendedor::create(['empresa_id' => $this->admin->empresa_id, 'nome' => 'Outra']);
        $minha = $this->vendaDe($anaVendedor);
        $alheia = $this->vendaDe($outra);

        Sanctum::actingAs(User::where('email', 'ana@loja.com')->first());

        // Edita a própria (e não consegue passá-la para outro vendedor).
        $this->putJson("/api/vendas/{$minha->id}", ['vendedor_id' => $outra->id, 'status' => 'concluida', 'desconto_percent' => 10])
            ->assertOk()
            ->assertJsonPath('vendedorId', $ana)
            ->assertJsonPath('total', 45);

        // A dos outros: nada de editar nem apagar.
        $this->putJson("/api/vendas/{$alheia->id}", ['vendedor_id' => $outra->id, 'status' => 'cancelada'])->assertForbidden();
        $this->deleteJson("/api/vendas/{$alheia->id}")->assertForbidden();
        $this->assertDatabaseHas('vendas', ['id' => $alheia->id, 'status' => 'concluida']);

        // Apaga a própria.
        $this->deleteJson("/api/vendas/{$minha->id}")->assertNoContent();
        $this->assertDatabaseMissing('vendas', ['id' => $minha->id]);
    }

    public function test_dono_continua_alterando_qualquer_venda(): void
    {
        $outra = Vendedor::create(['empresa_id' => $this->admin->empresa_id, 'nome' => 'Outra']);
        $venda = $this->vendaDe($outra);
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/vendas/{$venda->id}", ['vendedor_id' => $outra->id, 'status' => 'cancelada'])->assertOk();
        $this->deleteJson("/api/vendas/{$venda->id}")->assertNoContent();
    }

    public function test_vendedora_le_a_empresa_para_o_recibo_mas_nao_altera(): void
    {
        $this->criarVendedora();
        Sanctum::actingAs(User::where('email', 'ana@loja.com')->first());

        $this->getJson('/api/empresa')->assertOk()->assertJsonPath('razao_social', 'X');
        $this->putJson('/api/empresa', ['razao_social' => 'Hackeada'])->assertForbidden();
        $this->getJson('/api/comissoes')->assertForbidden();
    }
}
