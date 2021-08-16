<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\PriceDiscoPromoPlan;

class CombinationPlanKey extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'combination_key_name', 'combination_key', 'combination_key_code', 'status'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class, 'organisation_id', 'id');
    }

    public function pricingPlans()
    {
        return $this->hasMany(PriceDiscoPromoPlan::class,  'price_disco_promo_plan_id', 'id')->where('use_for', 'Pricing');
    }

    public function discounts()
    {
        return $this->hasMany(PriceDiscoPromoPlan::class,  'price_disco_promo_plan_id', 'id')->where('use_for', 'Discount');
    }

    public function bundlePromotions()
    {
        return $this->hasMany(PriceDiscoPromoPlan::class,  'price_disco_promo_plan_id', 'id')->where('use_for', 'Promotion');
    }
}
