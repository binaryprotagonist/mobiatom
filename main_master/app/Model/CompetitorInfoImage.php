<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\CompetitorInfo;

class CompetitorInfoImage extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'uuid', 'competitor_info_id', 'image_string'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public function competitorInfo()
    {
        return $this->belongsTo(CompetitorInfo::class,  'competitor_info_id', 'id');
    }
}
