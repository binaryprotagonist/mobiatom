<?php

namespace App\Model;

use App\Traits\Organisationid;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesmanActivityProfile extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'merchandiser_id', 'customer_id', 'activity_name', 'valid_from', 'valid_to'
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
    
    public function salesmanActivityProfileDetail()
    {
        return $this->hasMany(SalesmanActivityProfileDetail::class,  'salesman_activity_profile_id', 'id');
    }
    
    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }
    
    public function salesman()
    {
        return $this->belongsTo(User::class,  'merchandiser_id', 'id');
    }

}
