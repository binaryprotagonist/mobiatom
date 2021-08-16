<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;
use App\Model\StoragelocationDetail;

class Storagelocation extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'code', 'name', 'route_id', 'warehouse_id', 'loc_type'
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

    public function warehouseDetails()
    {
        return $this->hasMany(WarehouseDetail::class,  'warehouse_id', 'id');
    }

    public function storageLocationDetails()
    {
        return $this->hasMany(StoragelocationDetail::class,  'storage_location_id', 'id');
    }

    public function customFieldValueSave()
    {
        return $this->hasMany(CustomFieldValueSave::class,  'record_id', 'id');
    }

    public function getSaveData()
    {

        $this->route;
        $this->country;
        $this->warehouseDetails;
        return $this;
    }
}
