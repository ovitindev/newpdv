<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produto extends Model
{
    use BelongsToEmpresa;

    protected $fillable = ['empresa_id', 'categoria_id', 'marca_id', 'codigo', 'nome', 'preco', 'estoque', 'status'];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
            'estoque' => 'decimal:3',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }
}
