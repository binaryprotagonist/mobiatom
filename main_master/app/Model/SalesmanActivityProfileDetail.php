<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesmanActivityProfileDetail extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'salesman_activity_profile_id', 'module_name', 'status', 'priority'
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
        return $this->belongsTo(SalesmanActivityProfile::class,  'salesman_activity_profile_id', 'id');
    }
}
