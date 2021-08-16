<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Invoice;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\Reason;
use App\Model\PriceDiscoPromoPlan;

class DepotDamageExpiryDetail extends Model
{
    use SoftDeletes, LogsActivity;
    protected $table = 'depot_damage_expiry_detail';
    protected $fillable = [
        'uuid', 'depotdamageexpiry_id', 'item_id', 'item_uom_id', 'qty', 'reason_id'
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

    public function depotdamageexpiry()
    {
        return $this->belongsTo(DepotDamageExpiry::class,  'depotdamageexpiry_id', 'id');
    }

    public function item()
    {
        return $this->hasMany(Item::class,  'id', 'item_id');
    }

    public function itemUom()
    {
        return $this->hasMany(ItemUom::class,  'id', 'item_uom_id');
    }

    public function reason()
    {
        return $this->hasMany(Reason::class,  'id', 'reason_id');
    }

}
