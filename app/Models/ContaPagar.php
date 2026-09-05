<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class ContaPagar extends Model
{
    use BelongsToEmpresa;

    protected $table = 'contas_pagar';

    protected $fillable = ['empresa_id', 'descricao', 'categoria', 'valor', 'vencimento', 'status'];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'vencimento' => 'date',
        ];
    }
}
