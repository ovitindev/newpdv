<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use BelongsToEmpresa;

    protected $fillable = ['empresa_id', 'nome', 'documento', 'telefone', 'email', 'status'];
}
