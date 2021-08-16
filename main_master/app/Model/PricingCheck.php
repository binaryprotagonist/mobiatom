<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class PricingCheck extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'brand_id', 'salesman_id', 'customer_id', 'date', 'added_on'
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
        return $this->belongsTo(Organisation::class,  'organisation_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class,  'brand_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function pricingDetails()
    {
        return $this->hasMany(PricingCheckDetail::class,  'pricing_check_id', 'id');
    }

    public function getSaveData()
    {
        $this->brand;
        $this->salesman;
        $this->customer;
        $this->pricingDetails;
        if (count($this->pricingDetails)) {
            foreach ($this->pricingDetails as $key => $pricingDetails) {
                $this->pricingDetails[$key]->item = $pricingDetails->item;
                $this->pricingDetails[$key]->item_major_category = $pricingDetails->itemMajorCategory;
            }
        }
        return $this;
    }
}
