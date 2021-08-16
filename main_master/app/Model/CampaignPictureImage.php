<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class CampaignPictureImage extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'id_campaign_picture', 'image_string'
    ];

    protected static $logAttributes = ['*'];
    
    protected static $logOnlyDirty = false;

    public function campaignPicture()
    {
        return $this->belongsTo(CampaignPicture::class,  'id_campaign_picture', 'id');
    }
}
