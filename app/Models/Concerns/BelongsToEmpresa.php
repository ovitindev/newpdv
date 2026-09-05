<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Escopo automático por empresa (multi-tenant: banco único, tenant_id por tabela).
 * Toda query nesses models já vem filtrada pela empresa do usuário autenticado,
 * e novos registros recebem o empresa_id automaticamente.
 */
trait BelongsToEmpresa
{
    protected static function bootBelongsToEmpresa(): void
    {
        static::addGlobalScope('empresa', function (Builder $builder) {
            if (Auth::check()) {
                $builder->where($builder->getModel()->getTable().'.empresa_id', Auth::user()->empresa_id);
            }
        });

        static::creating(function ($model) {
            if (! $model->empresa_id && Auth::check()) {
                $model->empresa_id = Auth::user()->empresa_id;
            }
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
