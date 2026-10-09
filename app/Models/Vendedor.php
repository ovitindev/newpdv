<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendedor extends Model
{
    use BelongsToEmpresa;

    protected $table = 'vendedores';

    protected $fillable = ['empresa_id', 'user_id', 'nome', 'email', 'comissao', 'vale_semanal', 'meta_semanal', 'bonus_meta', 'status'];

    protected function casts(): array
    {
        return [
            'comissao' => 'decimal:2',
            'vale_semanal' => 'decimal:2',
            'meta_semanal' => 'decimal:2',
            'bonus_meta' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
