<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('arquivo_nome')->nullable();
            $table->string('arquivo_path')->nullable();
            $table->text('senha')->nullable();
            $table->string('titular')->nullable();
            $table->string('emissor')->nullable();
            $table->date('valido_ate')->nullable();
            $table->string('status')->default('pendente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados');
    }
};
