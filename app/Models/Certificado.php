<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class Certificado extends Model
{
    use BelongsToEmpresa;

    protected $fillable = ['empresa_id', 'arquivo_nome', 'arquivo_path', 'senha', 'titular', 'emissor', 'valido_ate', 'status'];

    protected $hidden = ['senha', 'arquivo_path'];

    protected function casts(): array
    {
        return [
            'senha' => 'encrypted',
            'valido_ate' => 'date',
        ];
    }
}
