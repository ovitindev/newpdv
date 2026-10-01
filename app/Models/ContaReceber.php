<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContaReceber extends Model
{
    use BelongsToEmpresa;

    protected $table = 'contas_receber';

    protected $fillable = ['empresa_id', 'venda_id', 'descricao', 'categoria', 'valor', 'vencimento', 'status', 'recorrente'];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'vencimento' => 'date',
            'recorrente' => 'boolean',
        ];
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class);
    }
}
