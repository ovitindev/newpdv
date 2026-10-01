<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pedidos_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            // Texto livre em vez de depender do cadastro de clientes — o
            // pedido pode ser anotado na hora, sem precisar cadastrar
            // ninguém. cliente_nome nulo = necessidade de compra interna,
            // sem cliente (ex: reposição de estoque que não foi pedida).
            $table->string('cliente_nome')->nullable();
            $table->string('cliente_contato')->nullable();
            $table->text('descricao');
            $table->unsignedInteger('quantidade')->default(1);
            $table->string('status')->default('pendente'); // pendente, comprado, disponivel, concluido, cancelado
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos_compra');
    }
};
