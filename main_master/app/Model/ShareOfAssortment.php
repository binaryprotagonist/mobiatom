<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\Model\ShareOfAssortmentOurBrand;
use App\User;

class ShareOfAssortment extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'salesman_id', 'customer_id', 'date', 'no_of_sku', 'added_on'
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

    public function shareOurBrand()
    {
        return $this->hasMany(ShareOfAssortmentOurBrand::class,  'share_of_assortment_id', 'id');
    }

    public function shareCompetitor()
    {
        return $this->hasMany(ShareOfAssortmentCompetitor::class,  'share_of_assortment_id', 'id');
    }

    public function salesman()
    {
        return $this->belongsTo(User::class,  'salesman_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class,  'customer_id', 'id');
    }

    public function getSaveData()
    {
        $this->shareOurBrand;
        $this->shareCompetitor;
        return $this;
    }
}
