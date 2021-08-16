<?php

namespace App\Model;

use App\Traits\Organisationid;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class ShareOfDisplay extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'salesman_id', 'customer_id', 'date', 'gandola_store', 'stands_store', 'added_on'
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

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function shareOfDisplayOurBrand()
    {
        return $this->hasMany(ShareOfDisplayOurBrand::class,  'share_of_display_id', 'id');
    }

    public function shareOfDisplayCompetitor()
    {
        return $this->hasMany(ShareOfDisplayCompetitor::class,  'share_of_display_id', 'id');
    }

    public function getSaveData()
    {
        $this->salesman;
        $this->customer;
        $this->shareOfDisplayOurBrand;
        $this->shareOfDisplayCompetitor;
        return $this;
    }
}
