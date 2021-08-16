<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\OutletProductCodeCustomer;
use App\Model\OutletProductCodeItem;

class OutletProductCode extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'name', 'code', 'status'
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

    public function outletProductCodeCustomers()
    {
        return $this->hasMany(OutletProductCodeCustomer::class,  'outlet_product_code_id', 'id');
    }

    public function outletProductCodeItems()
    {
        return $this->hasMany(OutletProductCodeItem::class,  'outlet_product_code_id', 'id');
    }

    public function getSaveData()
    {
        $this->outletProductCodeCustomers;
        if (count($this->outletProductCodeCustomers)) {
            foreach ($this->outletProductCodeCustomers as $key => $customer){
                $this->outletProductCodeCustomers[$key]->customer = $customer->customer;
            }
        }
        if (count($this->outletProductCodeItems)) {
            foreach ($this->outletProductCodeItems as $key => $item){
                $this->outletProductCodeItems[$key]->item = $item->item;
            }
        }
    
        return $this;
    }
}