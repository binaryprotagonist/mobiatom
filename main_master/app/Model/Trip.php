<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;
use App\Model\SalesmanInfo;
use App\User;

class Trip extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'route_id', 'salesman_id', 'trip_start', 'trip_start_date', 'trip_start_time', 'trip_end', 'trip_end_date', 'trip_end_time', 'trip_status', 'trip_from'
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

    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function salesmanInfo()
    {
        return $this->belongsTo(SalesmanInfo::class,  'salesman_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function users()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function customerVisit()
    {
        return $this->hasMany(CustomerVisit::class,  'trip_id', 'id');
    }
}
