<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Warehouse;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\WarehouseDetailLog;

class WarehouseDetail extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'uuid', 'warehouse_id', 'item_id', 'item_uom_id', 'qty', 'batch'
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

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class,  'warehouse_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function itemUom()
    {
        return $this->belongsTo(ItemUom::class,  'item_uom_id', 'id');
    }

    public function warehouseDetailLogs()
    {
        return $this->hasMany(WarehouseDetailLog::class,  'warehouse_detail_id', 'id');
    }

    public function getSaveData()
    {
        $this->warehouse;
        $this->item;
        $this->itemUom;
        return $this;
    }
}
