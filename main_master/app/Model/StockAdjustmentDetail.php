<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Invoice;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\PriceDiscoPromoPlan;

class StockAdjustmentDetail extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'stock_adjustment_id', 'item_id', 'item_uom_id', 'available_qty', 'new_qty', 'adjusted_qty'
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

    public function stockAdjustment()
    {
        return $this->belongsTo(StockAdjustment::class,  'stock_adjustment_id', 'id');
    }

    public function item()
    {
        return $this->hasMany(Item::class,  'id', 'item_id');
    }

    public function itemUom()
    {
        return $this->hasMany(ItemUom::class,  'id', 'item_uom_id');
    }

}
