<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait HasTenantVisibility
{
    public function scopeVisibleTo(Builder $query, string $tenant): Builder
    {
        $model = $query->getModel();
        if (! Schema::hasColumn($model->getTable(), 'tenant_id')) {
            return $query;
        }
        $grammar = $query->getQuery()->getGrammar();

        return $query->whereRaw('wms.entity_visible(?, '.$grammar->wrap($model->qualifyColumn($model->getKeyName())).'::text, '.$grammar->wrap($model->qualifyColumn('tenant_id')).'::text, ?)', [$model->getMorphClass(), $tenant]);
    }
}
