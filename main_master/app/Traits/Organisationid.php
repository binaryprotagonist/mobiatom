<?php
namespace App\Traits;
use Illuminate\Database\Eloquent\Builder;

trait Organisationid {

	protected static function bootOrganisationid()
    {
    	if (auth()->guard('api')->check()) {
	        static::creating(function ($model) {
	            $model->organisation_id = auth()->guard('api')->user()->organisation_id;
	        });

	        // if user is superadmin - usertype 0
	        if (auth()->guard('api')->user()->usertype != 0) {
	            static::addGlobalScope('organisation_id', function (Builder $builder) {
	                $builder->where('organisation_id', auth()->guard('api')->user()->organisation_id);
	            });
	        }
	    }
    }

}