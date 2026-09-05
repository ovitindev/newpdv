<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendaPagamento extends Model
{
    protected $fillable = ['venda_id', 'metodo', 'valor', 'parcelas'];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
        ];
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class);
    }
}
