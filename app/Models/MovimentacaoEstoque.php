<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentacaoEstoque extends Model
{
    use BelongsToEmpresa;

    protected $table = 'movimentacoes_estoque';

    protected $fillable = ['empresa_id', 'produto_id', 'tipo', 'quantidade', 'motivo', 'responsavel'];

    protected function casts(): array
    {
        return [
            'quantidade' => 'decimal:3',
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }
}
