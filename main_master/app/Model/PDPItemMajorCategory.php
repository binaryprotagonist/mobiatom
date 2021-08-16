<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\PriceDiscoPromoPlan;
use App\Model\ItemMajorCategory;

class PDPItemMajorCategory extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid', 'price_disco_promo_plan_id', 'item_major_category_id'
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

    public function itemMajorCategory()
    {
        return $this->belongsTo(ItemMajorCategory::class,  'item_major_category_id', 'id');
    }
    
}
