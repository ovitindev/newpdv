<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notas_fiscais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venda_id')->nullable()->constrained('vendas')->nullOnDelete();
            $table->string('numero');
            $table->string('serie')->default('1');
            $table->string('tipo');
            $table->string('cliente_nome')->nullable();
            $table->decimal('valor', 12, 2);
            $table->string('status')->default('autorizada');
            $table->string('chave_acesso', 44)->nullable();
            $table->timestamp('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_fiscais');
    }
};
