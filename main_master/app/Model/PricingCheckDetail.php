<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class PricingCheckDetail extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'pricing_check_id', 'date', 'price', 'item_id', 'item_major_category_id', 'srp'
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

    public function pricingCheck()
    {
        return $this->belongsTo(PricingCheck::class,  'pricing_check_id', 'id');
    }

    public function pricingCheckDetailPrices()
    {
        return $this->hasMany(PricingCheckDetailPrice::class,  'pricing_check_detail_id', 'id')
        ->orderBy('created_at', 'desc');
    }

    public function pricingCheckDetailPrice()
    {
        return $this->belongsTo(PricingCheckDetailPrice::class,  'id', 'pricing_check_detail_id')
        ->orderBy('id', 'asc');
    }

    public function item()
    {
        return $this->belongsTo(Item::class,  'item_id', 'id');
    }

    public function itemMajorCategory()
    {
        return $this->belongsTo(ItemMajorCategory::class,  'item_major_category_id', 'id');
    }
}
