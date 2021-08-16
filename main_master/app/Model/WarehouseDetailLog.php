<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Warehouse;
use App\Model\WarehouseDetail;
use App\Model\ItemUom;

class WarehouseDetailLog extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'uuid', 'warehouse_id', 'warehouse_detail_id', 'item_uom_id', 'qty', 'action_type'
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

    public function warehouseDetail()
    {
        return $this->belongsTo(WarehouseDetail::class,  'warehouse_detail_id', 'id');
    }

    public function itemUom()
    {
        return $this->belongsTo(ItemUom::class,  'item_uom_id', 'id');
    }
}
