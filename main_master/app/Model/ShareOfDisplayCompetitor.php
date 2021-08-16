<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\ShareOfDisplay;
use App\Model\Brand;

class ShareOfDisplayCompetitor extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'share_of_display_id', 'competitor_brand_id', 'competitor_catured_gandola', 'competitor_catured_stand', 'competitor_brand_share'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public function shareDisplay()
    {
        return $this->belongsTo(ShareOfDisplay::class,  'share_of_display_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(CompetitorInfo::class,  'competitor_brand_id', 'id');
    }
}
