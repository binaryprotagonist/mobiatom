<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;
use App\Model\WarehouseDetail;
use App\Model\WarehouseDetailLog;

class Warehouse extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'code', 'name', 'address', 'manager', 'depot_id', 'route_id', 'parent_warehouse_id', 'status', 'lat', 'lang'
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

    public function country()
    {
        return $this->belongsTo(Country::class,  'country_id', 'id');
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class,  'depot_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function parentWarehouses()
    {
        return $this->belongsTo(Warehouse::class,  'parent_warehouse_id', 'id');
    }

    public function warehouseDetails()
    {
        return $this->hasMany(WarehouseDetail::class,  'warehouse_id', 'id');
    }

    public function warehouseDetailLogs()
    {
        return $this->hasMany(WarehouseDetailLog::class,  'warehouse_id', 'id');
    }

    public function customFieldValueSave()
    {
        return $this->hasMany(CustomFieldValueSave::class,  'record_id', 'id');
    }

    public function getSaveData()
    {
        $this->depot;
        $this->route;
        $this->country;
        $this->parentWarehouses;
        $this->warehouseDetails;
        return $this;
    }
}
