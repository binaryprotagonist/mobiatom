<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\CompetitorInfo;
use App\Model\Brand;

class CompetitorInfoOurBrand extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'uuid', 'competitor_info_id', 'brand_id'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public function competitorInfo()
    {
        return $this->belongsTo(CompetitorInfo::class,  'competitor_info_id', 'id');
    }
    
    public function brand()
    {
        return $this->belongsTo(Brand::class,  'brand_id', 'id');
    }
}
