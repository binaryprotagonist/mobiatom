<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;


class CustomField extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;
    
    protected $fillable = [
        'uuid', 'organisation_id', 'module_id', 'field_type', 'field_label', 'status'
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

    public function customFieldValue()
    {
        return $this->hasMany(CustomFieldValue::class, 'custom_field_id', 'id');
    }
}
