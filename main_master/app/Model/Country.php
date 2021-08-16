<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\Region;
use App\User;
use App\Model\Warehouse;
use App\Model\PDPCountry;

class Country extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'name', 'country_code', 'dial_code', 'currency', 'currency_code', 'currency_symbol', 'status'
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

    public function users()
    {
        return $this->hasMany(User::class,  'country_id', 'id');
    }

    public function regions()
    {
        return $this->hasMany(Region::class,  'country_id', 'id');
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class,  'country_id', 'id');
    }

    public function PDPCountries()
    {
        return $this->hasMany(PDPCountry::class,  'country_id', 'id');
    }

    public function customFieldValueSave()
    {
        return $this->hasMany(CustomFieldValueSave::class,  'record_id', 'id');
    }
}
