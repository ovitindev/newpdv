<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    use BelongsToEmpresa;

    protected $table = 'perfis';

    protected $fillable = ['empresa_id', 'nome', 'descricao', 'permissoes'];

    protected function casts(): array
    {
        return [
            'permissoes' => 'array',
        ];
    }
}
