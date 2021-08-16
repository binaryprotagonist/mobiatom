<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Invoice;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\PurchaseOrder;
use App\Model\PriceDiscoPromoPlan;


class PurchaseOrderDetail extends Model
{
    use SoftDeletes, LogsActivity;
    protected $table = 'purchase_orders_detail';
    protected $fillable = [
        'uuid', 'purchase_order_id', 'item_id', 'item_uom_id', 'qty', 'price', 'discount', 'vat', 'net', 'excise', 'total'
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

    public function purchaseorder()
    {
        return $this->belongsTo(PurchaseOrder::class,  'purchase_order_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function itemUom()
    {
        return $this->belongsTo(ItemUom::class,  'item_uom_id', 'id');
    }

}
