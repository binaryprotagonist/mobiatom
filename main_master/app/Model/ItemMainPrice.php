<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Item;
use App\Model\ItemUom;

class ItemMainPrice extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'item_id', 'item_upc', 'item_uom_id', 'item_price', 'stock_keeping_unit'
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

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function itemUom()
    {
        return $this->belongsTo(ItemUom::class,  'item_uom_id', 'id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class,  'item_id', 'id');
    }

}
