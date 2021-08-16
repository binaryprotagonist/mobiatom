<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Area;
use App\Model\CustomerInfo;
use App\Model\SalesmanInfo;
use App\Model\RouteItemGrouping;
use App\Model\PDPRoute;

class Route extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'area_id', 'depot_id', 'route_code', 'route_name', 'status'
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

    public function areas()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id');
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class, 'depot_id', 'id');
    }

    public function customerInfos()
    {
        return $this->hasMany(CustomerInfo::class, 'route_id', 'id');
    }

    public function routeItemGroupings()
    {
        return $this->hasMany(RouteItemGrouping::class,  'route_id', 'id');
    }

    public function PDPRoutes()
    {
        return $this->hasMany(PDPRoute::class,  'route_id', 'id');
    }

    public function customFieldValueSave()
    {
        return $this->hasMany(CustomFieldValueSave::class,  'record_id', 'id');
    }
    public function van()
    {
        return $this->belongsTo(Van::class, 'van_id', 'id');
    }

    public function getSaveData()
    {
        $this->areas;
        $this->depot;
        $this->van;
        $this->routeItemGroupings;
        return $this;
    }
}