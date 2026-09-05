<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ConfigFiscalController;
use App\Http\Controllers\ContaPagarController;
use App\Http\Controllers\ContaReceberController;
use App\Http\Controllers\DevolucaoController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\FornecedorController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\MovimentacaoEstoqueController;
use App\Http\Controllers\NotaFiscalController;
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

    Route::get('/empresa', [EmpresaController::class, 'show']);
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
    Route::apiResource('produtos', ProdutoController::class);
    Route::get('/movimentacoes-estoque', [MovimentacaoEstoqueController::class, 'index']);
    Route::post('/movimentacoes-estoque', [MovimentacaoEstoqueController::class, 'store']);

    Route::apiResource('clientes', ClienteController::class);
    Route::apiResource('fornecedores', FornecedorController::class)->parameters(['fornecedores' => 'fornecedor']);

    Route::apiResource('vendas', VendaController::class)->only(['index', 'store', 'show', 'update']);
    Route::apiResource('devolucoes', DevolucaoController::class)
        ->only(['index', 'store', 'update'])
        ->parameters(['devolucoes' => 'devolucao']);

    Route::apiResource('contas-pagar', ContaPagarController::class)
        ->parameters(['contas-pagar' => 'contaPagar']);
    Route::apiResource('contas-receber', ContaReceberController::class)
        ->parameters(['contas-receber' => 'contaReceber']);

    Route::get('/notas-fiscais', [NotaFiscalController::class, 'index']);
    Route::post('/notas-fiscais', [NotaFiscalController::class, 'store']);
    Route::put('/notas-fiscais/{notaFiscal}', [NotaFiscalController::class, 'update']);
});
