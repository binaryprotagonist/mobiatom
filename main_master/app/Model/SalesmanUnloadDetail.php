<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\SalesmanUnload;

class SalesmanUnloadDetail extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'salesman_unload_id', 'item_id', 'item_uom', 'unload_qty', 'unload_date', 'unload_type', 'reason', 'status'
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

    public function salesmanUnload()
    {
        return $this->hasMany(SalesmanUnload::class,  'salesman_load_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function itemUom()
    {
        return $this->belongsTo(ItemUom::class,  'item_uom', 'id');
    }
}
