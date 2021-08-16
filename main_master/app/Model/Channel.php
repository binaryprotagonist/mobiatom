<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\CustomerInfo;
use App\Model\PDPChannel;

class Channel extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'parent_id', 'name', 'node_level', 'status'
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

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->with('children');
    }

    public function customerInfos()
    {
        return $this->hasMany(CustomerInfo::class, 'channel_id', 'id');
    }

    public function PDPChannels()
    {
        return $this->hasMany(PDPChannel::class,  'channel_id', 'id');
    }
}
