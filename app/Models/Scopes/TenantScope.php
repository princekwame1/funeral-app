<?php

namespace App\Models\Scopes;

use App\Support\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $current = app(CurrentTenant::class);
        if (! $current->isSet()) {
            return; // super admins / unscoped contexts see everything
        }

        $builder->where($model->qualifyColumn('tenant_id'), $current->id());
    }
}
