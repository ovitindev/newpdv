<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $empresa = Empresa::firstOrCreate(
            ['cnpj' => '00000000000000'],
            [
                'razao_social' => 'Minha Empresa',
                'nome_fantasia' => 'Minha Empresa',
                'regime_tributario' => 'Simples Nacional',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin'],
            [
                'empresa_id' => $empresa->id,
                'name' => 'Administrador',
                'password' => Hash::make('@@123admin'),
                'cargo' => 'Administrador',
                'status' => 'ativo',
            ]
        );
    }
}
