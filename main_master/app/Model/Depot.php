<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;
use App\Model\Region;
use App\Model\Area;
use App\Model\Warehouse;
use App\Model\Order;

class Depot extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'user_id', 'region_id', 'area_id', 'depot_code', 'depot_name', 'depot_manager', 'depot_manager_contact', 'status'
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

    public function user()
    {
        return $this->belongsTo(User::class,  'user_id', 'id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class,  'region_id', 'id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class,  'area_id', 'id');
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class,  'depot_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class,  'depot_id', 'id');
    }

    public function customFieldValueSave()
    {
        return $this->hasMany(CustomFieldValueSave::class,  'record_id', 'id');
    }

    public function getSaveData()
    {
        $this->region;
        $this->area;
        $this->customFieldValueSave;
        return $this;
    }
}