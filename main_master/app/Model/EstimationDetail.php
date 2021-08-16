<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\Model\Country;
use App\Model\Route;

class EstimationDetail extends Model
{
    use SoftDeletes, LogsActivity;
    protected $table = 'estimation_detail';
    protected $fillable = [
        'uuid', 'estimation_id', 'item_id', 'item_uom_id', 'item_qty', 'item_price', 'item_discount_amount', 'item_vat', 'item_excise', 'item_grand_total', 'item_net'
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
	
	public function Estimation()
    {
        return $this->belongsTo(Estimation::class,  'estimation_id', 'id');
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
