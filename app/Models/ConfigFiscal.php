<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;

class ConfigFiscal extends Model
{
    use BelongsToEmpresa;

    protected $table = 'config_fiscais';

    protected $fillable = ['empresa_id', 'ambiente', 'serie_nfce', 'proximo_numero_nfce', 'serie_nfe', 'proximo_numero_nfe', 'csc', 'id_csc'];

    protected $hidden = ['csc'];

    protected function casts(): array
    {
        return [
            'csc' => 'encrypted',
        ];
    }
}
