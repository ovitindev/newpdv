<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Fornecedor extends Model
{
    use BelongsToEmpresa;

    protected $table = 'fornecedores';

    protected $fillable = ['empresa_id', 'nome', 'documento', 'telefone', 'email', 'categoria', 'status'];
}
