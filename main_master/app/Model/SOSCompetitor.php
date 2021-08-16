<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\SOS;
use App\Model\Brand;

class SOSCompetitor extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'sos_id', 'competitor_brand_id', 'competitor_catured_block', 'competitor_catured_shelves', 'competitor_brand_share'
    ];

    protected static $logAttributes = ['*'];

    protected static $logOnlyDirty = false;

    public function sos()
    {
        return $this->belongsTo(SOS::class,  'sos_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(CompetitorInfo::class,  'competitor_brand_id', 'id');
    }
}
