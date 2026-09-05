<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaFiscal extends Model
{
    use BelongsToEmpresa;

    protected $table = 'notas_fiscais';

    protected $fillable = ['empresa_id', 'venda_id', 'numero', 'serie', 'tipo', 'cliente_nome', 'valor', 'status', 'chave_acesso', 'data'];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data' => 'datetime',
        ];
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class);
    }
}
