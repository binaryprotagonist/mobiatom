<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\VanType;
use App\Model\VanCategory;

class Van extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'van_code', 'plate_number', 'description', 'capacity', 'van_type_id', 'van_category_id', 'van_status'
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

    public function type()
    {
        return $this->belongsTo(VanType::class,  'van_type_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(VanCategory::class,  'van_category_id', 'id');
    }

    public function customFieldValueSave()
    {
        return $this->hasMany(CustomFieldValueSave::class,  'record_id', 'id');
    }    
}