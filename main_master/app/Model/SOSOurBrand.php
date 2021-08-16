<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\SOS;
use App\Model\Brand;
use App\Model\Item;
use App\Model\ItemMajorCategory;

class SOSOurBrand extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'sos_id', 'brand_id', 'item_major_category_id', 'item_id', 'catured_block', 'catured_shelves', 'brand_share'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public function sos()
    {
        return $this->belongsTo(SOS::class, 'sos_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function itemMajorCategory()
    {
        return $this->belongsTo(ItemMajorCategory::class, 'item_major_category_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
