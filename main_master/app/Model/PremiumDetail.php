<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class PremiumDetail extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    // protected $table = 'load_request';
    protected $fillable = [
        'uuid', 'name', 'valid_from','valid_to', 'type', 'qty', 'amount', 'invoice_amount'
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

    public function PremiumCustomer()
    {
        return $this->hasMany(PremiumCustomer::class,  'premium_detail_id', 'id');
    }
    
    public function Organisation()
    {
        return $this->belongsTo(Organisation::class,  'organisation_id', 'id');
    } 

    public function PremiumCustomerGet()
    {
        return $this->hasMany(PremiumCustomer::class,  'premium_detail_id', 'id')->with('CustomerInfo', 'CustomerInfo.user');
    }

    public function getSaveData()
    {        
         $this->PremiumCustomerGet;
        return $this;
    }
}
