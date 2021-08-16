<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Model\SalesTarget;
use App\Model\SalesTargetDetail;
use App\Model\Item;
use App\Model\ItemUom;
use App\Model\PriceDiscoPromoPlan;

class SalesItemTargetDetail extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'uuid', 'sales_target_id', 'item_id', 'item_uom_id','ApplyOn'];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = (string) \Uuid::generate();
        });
    }
    
    public function salesTarget()
    {
        return $this->belongsTo(SalesTarget::class,  'sales_target_id', 'id');
    }
    
    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
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

    public function salesTargetDetails()
    {
        return $this->hasMany(SalesTargetDetail::class,  'sales_target_id', 'sales_target_id');
    }
}
