<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Route;
use App\Model\RouteItemGroupingDetail;
use App\User;

class RouteItemGrouping extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'merchandiser_id', 'route_id', 'name', 'code', 'status'
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
        return $this->belongsTo(Organisation::class, 'organisation_id', 'id');
    }

    // public function routeItemGroupingDetails()
    // {
    //     return $this->hasMany(RouteItemGroupingDetails::class, 'route_item_grouping_id', 'id');
    // }

    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class, 'merchandiser_id', 'id');
    }

    public function routeItemGroupingDetails()
    {
        return $this->hasMany(RouteItemGroupingDetail::class, 'route_item_grouping_id', 'id');
    }

    public function getSaveData()
    {
        $this->route;
        $this->salesman;
        $this->routeItemGroupingDetails;
        return $this;
    }
}
