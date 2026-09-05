<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    use BelongsToEmpresa;

    protected $table = 'vendedores';

    protected $fillable = ['empresa_id', 'nome', 'email', 'comissao', 'status'];

    protected function casts(): array
    {
        return [
            'comissao' => 'decimal:2',
        ];
    }
}
