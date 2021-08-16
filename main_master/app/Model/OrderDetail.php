<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Order;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\PriceDiscoPromoPlan;

class OrderDetail extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'order_id', 'item_id', 'item_uom_id', 'discount_id', 'is_free', 'is_item_poi', 'promotion_id', 'item_qty', 'item_price', 'item_gross', 'item_discount_amount', 'item_net', 'item_vat', 'item_excise', 'item_grand_total', 'order_status', 'open_qty', 'delivered_qty'
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

    public function order()
    {
        return $this->belongsTo(Order::class,  'order_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function itemMainPrice()
    {
        return $this->belongsTo(ItemMainPrice::class,  'item_id', 'id');
    }

    public function itemUom()
    {
        return $this->belongsTo(ItemUom::class,  'item_uom_id', 'id');
    }

    public function discount()
    {
        return $this->belongsTo(PriceDiscoPromoPlan::class,  'discount_id', 'id');
    }

    public function promotion()
    {
        return $this->belongsTo(PriceDiscoPromoPlan::class,  'promotion_id', 'id');
    }

    public function pricediscopromoplan()
    {
        return $this->belongsTo(PriceDiscoPromoPlan::class, 'promotion_id', 'id');
    }

    public function pricediscopromoplan_discount()
    {
        return $this->belongsTo(PriceDiscoPromoPlan::class, 'discount_id', 'id');
    }
}
