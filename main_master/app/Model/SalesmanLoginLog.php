<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class SalesmanLoginLog extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'user_id', 'ip', 'device_token', 'vesion', 'device_name', 'imei_number'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public function user()
    {
        return $this->belongsTo(User::class,  'user_id', 'id');
    }
}