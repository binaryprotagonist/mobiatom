<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\ShareOfAssortment;

class ShareOfAssortmentOurBrand extends Model
{
    use LogsActivity;

    protected $fillable = [
        'share_of_assortment_id', 'brand_id', 'captured_sku', 'brand_share', 'item_major_category_id'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public function shareAssortment()
    {
        return $this->belongsTo(ShareOfAssortment::class,  'share_of_assortment_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class,  'brand_id', 'id');
    }

    public function itemMajorCategory()
    {
        return $this->belongsTo(ItemMajorCategory::class, 'item_major_category_id', 'id');
    }
}
