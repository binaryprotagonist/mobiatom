<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

use App\Model\Organisation;
use App\Model\SalesPerson;
use App\Model\CustomerInfo;
use App\Model\EstimationDetail;
use App\Model\AssignTemplate;
use App\User;

class Template extends Model
{
    use SoftDeletes, LogsActivity;
    // protected $table = 'template';
    protected $fillable = [
        'uuid', 'template_name', 'template_image', 'is_default'
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
    
    public function assginTempalate()
    {
        return $this->belongsTo(AssignTemplate::class, 'id', 'template_id');
    }
	
}
