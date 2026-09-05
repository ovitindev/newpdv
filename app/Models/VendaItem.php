<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendaItem extends Model
{
    protected $table = 'venda_itens';

    protected $fillable = ['venda_id', 'produto_id', 'nome', 'codigo', 'preco', 'quantidade', 'avulso'];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'quantidade' => 'decimal:3',
            'avulso' => 'boolean',
        ];
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class);
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
