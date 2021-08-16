<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\ShareOfAssortment;

class ShareOfAssortmentCompetitor extends Model
{
    use LogsActivity;

    protected $fillable = [
        'share_of_assortment_id', 'competitor_info_id', 'competitor_sku', 'brand_share'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public function shareAssortment()
    {
        return $this->belongsTo(ShareOfAssortment::class,  'share_of_assortment_id', 'id');
    }

    public function competitorInfo()
    {
        return $this->belongsTo(CompetitorInfo::class,  'competitor_info_id', 'id');
    }
}
