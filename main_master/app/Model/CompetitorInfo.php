<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\User;

class CompetitorInfo extends Model
{
    use LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'trip_id', 'salesman_id', 'company', 'item', 'price', 'brand', 'note'
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

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function competitorInfoImage()
    {
        return $this->hasMany(CompetitorInfoImage::class,  'competitor_info_id', 'id');
    }

    public function competitorInfoOurBrand()
    {
        return $this->hasMany(CompetitorInfoOurBrand::class,  'competitor_info_id', 'id');
    }

    public function getSaveData()
    {
        $this->salesman;
        $this->competitorInfoImage;
        return $this;
    }
}
