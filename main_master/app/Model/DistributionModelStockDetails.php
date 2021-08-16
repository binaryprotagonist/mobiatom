<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\User;
use App\Model\Distribution;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\DistributionModelStock;

class DistributionModelStockDetails extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'distribution_model_stock_id', 'distribution_id', 'item_id', 'item_uom_id', 'capacity'
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

    public function distribution()
    {
        return $this->belongsTo(Distribution::class,  'distribution_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function itemUom()
    {
        return $this->belongsTo(ItemUom::class,  'item_uom_id', 'id');
    }

    public function distributionModelStock()
    {
        return $this->belongsTo(DistributionModelStock::class,  'distribution_model_stock_id', 'id');
    }
}
