<?php

namespace App\Model;

use App\Traits\Organisationid;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class SessionEndorsementRequest extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid',
        'organisation_id',
        'route_id',
        'salesman_id',
        'supervisor_id',
        'trip_id',
        'status'
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class,  'supervisor_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class,  'trip_id', 'id');
    }
}
