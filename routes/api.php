<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\ComissaoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfigFiscalController;
use App\Http\Controllers\ContaPagarController;
use App\Http\Controllers\ContaReceberController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevolucaoController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\MovimentacaoEstoqueController;
use App\Http\Controllers\NotaFiscalController;
use App\Http\Controllers\PedidoCompraController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VendaController;
use App\Http\Controllers\VendedorController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:6,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Liberado também para o usuário-vendedor (app da vendedora): consulta de
    // produto, clientes, pedidos e vendas (que o controller restringe às
    // vendas dele).
    Route::apiResource('produtos', ProdutoController::class)->only(['index', 'show']);
    Route::apiResource('clientes', ClienteController::class)->except(['destroy']);
    Route::apiResource('pedidos-compra', PedidoCompraController::class)
        ->parameters(['pedidos-compra' => 'pedidoCompra']);
    // Todos veem as vendas; editar/excluir é restrito às próprias (VendaController).
    Route::apiResource('vendas', VendaController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::get('/comissao', [ComissaoController::class, 'show']);
    // Dados da empresa só para leitura (cabeçalho do recibo).
    Route::get('/empresa', [EmpresaController::class, 'show']);

    Route::middleware('somente.admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'overview']);

        Route::put('/empresa', [EmpresaController::class, 'update']);
        Route::post('/empresa/logo', [EmpresaController::class, 'uploadLogo']);

        Route::apiResource('usuarios', UsuarioController::class)->except(['show']);
        Route::apiResource('vendedores', VendedorController::class)->parameters(['vendedores' => 'vendedor']);
        Route::apiResource('perfis', PerfilController::class)->parameters(['perfis' => 'perfil']);

        Route::get('/certificado', [CertificadoController::class, 'show']);
        Route::post('/certificado', [CertificadoController::class, 'store']);
        Route::delete('/certificado', [CertificadoController::class, 'destroy']);

        Route::get('/config-fiscal', [ConfigFiscalController::class, 'show']);
        Route::put('/config-fiscal', [ConfigFiscalController::class, 'update']);

        Route::apiResource('categorias', CategoriaController::class);
        Route::apiResource('marcas', MarcaController::class);
        Route::apiResource('produtos', ProdutoController::class)->except(['index', 'show']);
        Route::get('/movimentacoes-estoque', [MovimentacaoEstoqueController::class, 'index']);
        Route::post('/movimentacoes-estoque', [MovimentacaoEstoqueController::class, 'store']);

        Route::delete('/clientes/{cliente}', [ClienteController::class, 'destroy']);
        Route::apiResource('fornecedores', FornecedorController::class)->parameters(['fornecedores' => 'fornecedor']);

        Route::get('/comissoes', [ComissaoController::class, 'index']);
        Route::apiResource('devolucoes', DevolucaoController::class)
            ->only(['index', 'store', 'update'])
            ->parameters(['devolucoes' => 'devolucao']);

        Route::post('/contas-pagar/replicar', [ContaPagarController::class, 'replicar']);
        Route::apiResource('contas-pagar', ContaPagarController::class)
            ->parameters(['contas-pagar' => 'contaPagar']);
        Route::post('/contas-receber/replicar', [ContaReceberController::class, 'replicar']);
        Route::apiResource('contas-receber', ContaReceberController::class)
            ->parameters(['contas-receber' => 'contaReceber']);

        Route::get('/notas-fiscais', [NotaFiscalController::class, 'index']);
        Route::post('/notas-fiscais', [NotaFiscalController::class, 'store']);
        Route::put('/notas-fiscais/{notaFiscal}', [NotaFiscalController::class, 'update']);
    });
});
