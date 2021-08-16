<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;

class SalesmanTripInfos extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'trips_id', 'route_id', 'salesman_id', 'status'
    ];

    protected $table = 'salesman_trip_infos';

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    
}