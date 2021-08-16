<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\User;

class AssetTrackingPostImage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'uuid', 'asset_tracking_id', 'asset_tracking_post_id', 'image_string'
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

    public function organisation()
    {
        return $this->belongsTo(Organisation::class,  'organisation_id', 'id');
    }

    public function assetTracking()
    {
        return $this->belongsTo(AssetTracking::class,  'asset_tracking_id', 'id');
    }

    public function assetTrackingPost()
    {
        return $this->belongsTo(AssetTrackingPost::class,  'asset_tracking_post_id', 'id');
    }
}