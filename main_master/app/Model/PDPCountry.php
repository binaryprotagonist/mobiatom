<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\PriceDiscoPromoPlan;
use App\Model\Country;

class PDPCountry extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'price_disco_promo_plan_id', 'country_id'
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

    public function priceDiscoPromoPlan()
    {
        return $this->belongsTo(PriceDiscoPromoPlan::class,  'price_disco_promo_plan_id', 'id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class,  'country_id', 'id');
    }
}
