<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\User;

class PremiumCustomer extends Model
{
    use SoftDeletes, LogsActivity;
     protected $fillable = [
        'uuid', 'customer_id', 'premium_detail_id'
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

    public function CustomerInfo()
    {
        return $this->belongsTo(CustomerInfo::class,  'customer_id', 'id');
    } 
}
