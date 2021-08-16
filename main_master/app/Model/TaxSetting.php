<?php

namespace App\Model;

use App\Traits\Organisationid;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class TaxSetting extends Model
{
    use LogsActivity, Organisationid;

    protected $fillable = [
        'uuid', 'organisation_id', 'is_tax_registered', 'trn_text', 'number', 'register_date', 'composition_scheme', 'international_trade', 'composition_scheme_percentage', 'digital_services'
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
}
