<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_fiscais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('ambiente')->default('homologacao');
            $table->string('serie_nfce')->default('1');
            $table->unsignedInteger('proximo_numero_nfce')->default(1);
            $table->string('serie_nfe')->default('1');
            $table->unsignedInteger('proximo_numero_nfe')->default(1);
            $table->text('csc')->nullable();
            $table->string('id_csc')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_fiscais');
    }
};
