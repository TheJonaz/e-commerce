<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('currentTenant')) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', app('currentTenant')->id);
            }
        });

        static::creating(function ($model) {
            if (! $model->tenant_id && app()->bound('currentTenant')) {
                $model->tenant_id = app('currentTenant')->id;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
