<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\OutletProductCode;
use App\Model\Item;

class OutletProductCodeItem extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'outlet_product_code_id', 'item_id', 'outlet_product_code'
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

    public function outletProductCode()
    {
        return $this->belongsTo(OutletProductCode::class,  'outlet_product_code_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }
}
