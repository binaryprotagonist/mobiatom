<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\OutletProductCode;
use App\User;

class OutletProductCodeCustomer extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'outlet_product_code_id', 'customer_id'
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

    public function outletProductCode()
    {
        return $this->belongsTo(OutletProductCode::class,  'outlet_product_code_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }
}
