<?php
namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Model\Invoice;
use App\Model\Module;
use App\Model\CustomField;
use App\User;


class CustomFieldValue2 extends Model
{
    use SoftDeletes, LogsActivity;
    
    protected $fillable = [
        'uuid', 'ModuleId', 'ModuleType', 'CustimFieldId', 'CustomFieldValue','status'
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
    
    public function moduledeials()
    {
        return $this->belongsTo(Module::class,  'ModuleType', 'id');
    }

	public function CustomfieldDetails()
    {
        return $this->belongsTo(CustomField::class,  'CustimFieldId', 'id');
    }
   
}
