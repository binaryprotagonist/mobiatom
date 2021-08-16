<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\ShareOfDisplay;
use App\Model\Brand;
use App\Model\Item;
use App\Model\ItemMajorCategory;

class ShareOfDisplayOurBrand extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'share_of_display_id', 'brand_id', 'item_major_category_id', 'catured_gandola', 'catured_stand', 'brand_share'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public function shareDisplay()
    {
        return $this->belongsTo(ShareOfDisplay::class, 'share_of_display_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function itemMajorCategory()
    {
        return $this->belongsTo(ItemMajorCategory::class, 'item_major_category_id', 'id');
    }
}
