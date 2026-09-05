<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    use BelongsToEmpresa;

    protected $fillable = ['empresa_id', 'nome', 'status'];

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }
}
