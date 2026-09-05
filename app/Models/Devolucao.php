<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Devolucao extends Model
{
    use BelongsToEmpresa;

    protected $table = 'devolucoes';

    protected $fillable = ['empresa_id', 'venda_id', 'produto', 'motivo', 'valor', 'status'];

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
