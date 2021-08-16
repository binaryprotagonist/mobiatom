<?php

namespace App\Model;

use App\Traits\Organisationid;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Organisation;
use App\Model\SOSCompetitor;
use App\Model\SOSOurBrand;

class SOS extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'salesman_id', 'customer_id', 'date', 'block_store', 'no_of_Shelves', 'added_on'
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

    public function sosOurBrand()
    {
        return $this->hasMany(SOSOurBrand::class,  'sos_id', 'id');
    }

    public function sosCompetitor()
    {
        return $this->hasMany(SOSCompetitor::class,  'sos_id', 'id');
    }

    public function getSaveData()
    {
        $this->salesman;
        $this->customer;
        $this->sosOurBrand;
        $this->sosCompetitor;
        return $this;
    }
}
