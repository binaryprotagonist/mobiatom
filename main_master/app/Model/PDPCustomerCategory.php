<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\PriceDiscoPromoPlan;
use App\Model\CustomerCategory;

class PDPCustomerCategory extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'price_disco_promo_plan_id', 'customer_category_id'
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

    public function customerCategory()
    {
        return $this->belongsTo(CustomerCategory::class,  'customer_category_id', 'id');
    }
}
