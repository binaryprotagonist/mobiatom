<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;
use App\Model\SalesmanLoad;
use App\Model\Route;
use App\Model\Depot;

class SalesmanLoadDetails extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'salesman_load_id', 'route_id', 'depot_id', 'item_id', 'salesman_id', 'load_date', 'item_uom', 'load_qty', 'requested_qty', 'requested_item_uom_id'
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

    public function salesmanLoad()
    {
        return $this->belongsTo(SalesmanLoad::class,  'salesman_load_id', 'id');
    }

    public function depot()
    {
        return $this->belongsTo(Depot::class,  'depot_id', 'id');
    }

    public function route()
    {
        return $this->belongsTo(Route::class,  'route_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function itemUOM()
    {
        return $this->belongsTo(ItemUom::class, 'item_uom', 'id');
    }

    public function salesmanInfo()
    {
        return $this->belongsTo(SalesmanInfo::class,  'salesman_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function warehouseDetailLogs()
    {
        return $this->hasMany(WarehouseDetailLog::class,  'warehouse_id', 'id');
    }
}
