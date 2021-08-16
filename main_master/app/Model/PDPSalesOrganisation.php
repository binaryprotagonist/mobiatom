<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\PriceDiscoPromoPlan;
use App\Model\SalesOrganisation;

class PDPSalesOrganisation extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'price_disco_promo_plan_id', 'sales_organisation_id'
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

    public function salesOrganisation()
    {
        return $this->belongsTo(SalesOrganisation::class,  'sales_organisation_id', 'id');
    }
}
