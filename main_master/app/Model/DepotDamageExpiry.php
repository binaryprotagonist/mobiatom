<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;

class DepotDamageExpiry extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $table = 'depot_damage_expiry';
    
    protected $fillable = [
        'uuid', 'organisation_id', 'depot_id', 'reference_code', 'date'
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

    public function depot()
    {
        return $this->belongsTo(Depot::class,  'depot_id', 'id');
    }

    public function depotdamageexpiryDetail()
    {
        return $this->hasMany(DepotDamageExpiryDetail::class,  'depotdamageexpiry_id', 'id');
    }

    public function getSaveData()
    {
        $this->depot;
        $this->depotdamageexpiryDetail;
        if (is_object($this->depotdamageexpiryDetail->item)) {
            $this->depotdamageexpiryDetail->item;
        }

        if (is_object($this->depotdamageexpiryDetail->itemUom)) {
            $this->depotdamageexpiryDetail->itemUom;
        }

        if (is_object($this->depotdamageexpiryDetail->reason)) {
            $this->depotdamageexpiryDetail->reason;
        }

        return $this;
    }
}
