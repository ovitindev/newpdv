<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendedores', function (Blueprint $table) {
            // Além do % de comissão (coluna "comissao"): ajuda de custo fixa por
            // semana (ex: vale-gasolina) e bônus quando a venda da semana passa
            // da meta. Meta 0 = sem bônus.
            $table->decimal('vale_semanal', 10, 2)->default(0)->after('comissao');
            $table->decimal('meta_semanal', 12, 2)->default(0)->after('vale_semanal');
            $table->decimal('bonus_meta', 10, 2)->default(0)->after('meta_semanal');
        });
    }

    public function down(): void
    {
        Schema::table('vendedores', function (Blueprint $table) {
            $table->dropColumn(['vale_semanal', 'meta_semanal', 'bonus_meta']);
        });
    }
};
