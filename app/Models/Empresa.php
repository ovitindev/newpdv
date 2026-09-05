<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'inscricao_estadual',
        'regime_tributario',
        'telefone',
        'email',
        'logradouro',
        'bairro',
        'cidade',
        'uf',
        'cep',
        'logo_path',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function certificado(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Certificado::class);
    }

    public function configFiscal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ConfigFiscal::class);
    }
}
