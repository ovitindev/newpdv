<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoCompra extends Model
{
    use BelongsToEmpresa;

    protected $table = 'pedidos_compra';

    protected $fillable = [
        'empresa_id', 'cliente_id', 'cliente_nome', 'cliente_contato',
        'descricao', 'quantidade', 'status', 'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'quantidade' => 'integer',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
