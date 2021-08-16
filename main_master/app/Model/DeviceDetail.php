<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class DeviceDetail extends Model
{
    use Organisationid;

    protected $fillable = [
        'user_id', 'ip', 'device_token', 'device_name', 'type'
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

    public function user()
    {
        return $this->belongsTo(User::class,  'user_id', 'id');
    }
}
