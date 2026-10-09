<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendedores', function (Blueprint $table) {
            // Login da vendedora no app (PWA). Nulo = vendedor só de cadastro,
            // sem acesso ao sistema.
            $table->foreignId('user_id')->nullable()->unique()->after('empresa_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendedores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
