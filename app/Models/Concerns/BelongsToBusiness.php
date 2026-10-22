<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\BusinessContext;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $query) {
            $business = app(BusinessContext::class)->current();
            $business ? $query->where($query->getModel()->qualifyColumn('business_id'), $business->id) : $query->whereRaw('1 = 0');
        });
        static::saving(function ($model) {
            $business = app(BusinessContext::class)->current();
            if (! $business || ($model->business_id && (int) $model->business_id !== (int) $business->id)) {
                throw new LogicException('Invalid business context.');
            } $model->business_id = $business->id;
        });
        static::deleting(function ($model) {
            $business = app(BusinessContext::class)->current();
            if (! $business || (int) $model->business_id !== (int) $business->id) {
                throw new LogicException('Invalid business context.');
            }
        });
    }
}
