<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use App\Models\Venda;
use App\Models\Vendedor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComissaoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-09 15:00:00'); // sexta-feira
        $empresa = Empresa::create([
            'razao_social' => 'X', 'nome_fantasia' => 'X', 'cnpj' => '1', 'regime_tributario' => 'Simples Nacional',
        ]);
        $this->admin = User::factory()->create(['empresa_id' => $empresa->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function vendedora(): Vendedor
    {
        Sanctum::actingAs($this->admin);
        $id = $this->postJson('/api/vendedores', [
            'nome' => 'Ana', 'email' => 'ana@loja.com', 'senha' => 'segredo1',
            'comissao' => 3, 'vale_semanal' => 30, 'meta_semanal' => 3000, 'bonus_meta' => 50,
        ])->assertCreated()->assertJsonPath('valeSemanal', '30.00')->json('id');
        app('auth')->forgetGuards();

        return Vendedor::find($id);
    }

    private function venda(Vendedor $v, float $total, string $quando): void
    {
        $venda = Venda::create([
            'empresa_id' => $this->admin->empresa_id, 'vendedor_id' => $v->id,
            'subtotal' => $total, 'total' => $total, 'status' => 'concluida',
        ]);
        $venda->forceFill(['created_at' => $quando])->save();
    }

    public function test_abaixo_da_meta_paga_comissao_mais_vale(): void
    {
        $v = $this->vendedora();
        $this->venda($v, 1000, '2026-10-06 10:00:00'); // terça desta semana

        Sanctum::actingAs(User::where('email', 'ana@loja.com')->first());
        $this->getJson('/api/comissao?tipo=semana')
            ->assertOk()
            ->assertJsonPath('totais.comissao', 30)
            ->assertJsonPath('totais.vale', 30)
            ->assertJsonPath('totais.bonus', 0)
            ->assertJsonPath('totais.total', 60)
            ->assertJsonPath('semanas.0.faltaParaMeta', 2000);
    }

    public function test_exatamente_na_meta_nao_da_bonus_e_acima_da_meta_da(): void
    {
        $v = $this->vendedora();
        $this->venda($v, 3000, '2026-10-06 10:00:00');
        Sanctum::actingAs(User::where('email', 'ana@loja.com')->first());
        $this->getJson('/api/comissao')->assertJsonPath('totais.bonus', 0)->assertJsonPath('totais.total', 120);

        $this->venda($v, 200, '2026-10-08 10:00:00');
        $this->getJson('/api/comissao')
            ->assertJsonPath('totais.vendas', 3200)
            ->assertJsonPath('totais.bonus', 50)
            ->assertJsonPath('totais.total', 176);
    }

    public function test_mes_soma_semanas_com_segunda_dentro_do_mes_e_ignora_canceladas(): void
    {
        $v = $this->vendedora();
        $this->venda($v, 4000, '2026-10-05 10:00:00'); // semana 05/10 (meta batida)
        $this->venda($v, 500, '2026-10-12 10:00:00');  // futuro: ignorado
        $cancelada = Venda::create(['empresa_id' => $this->admin->empresa_id, 'vendedor_id' => $v->id, 'subtotal' => 900, 'total' => 900, 'status' => 'cancelada']);
        $cancelada->forceFill(['created_at' => '2026-10-06 10:00:00'])->save();

        Sanctum::actingAs(User::where('email', 'ana@loja.com')->first());
        // Segundas de outubro/2026 até hoje: 05/10 apenas (a de 12/10 ainda não começou).
        $this->getJson('/api/comissao?tipo=mes')
            ->assertOk()
            ->assertJsonCount(1, 'semanas')
            ->assertJsonPath('totais.vendas', 4000)
            ->assertJsonPath('totais.total', 200); // 120 + 30 + 50
    }

    public function test_admin_consulta_por_vendedor_id(): void
    {
        $v = $this->vendedora();
        Sanctum::actingAs($this->admin);

        $this->getJson("/api/comissao?vendedor_id={$v->id}")->assertOk()->assertJsonPath('vendedor.nome', 'Ana');
        $this->getJson('/api/comissao')->assertUnprocessable();
    }
}
