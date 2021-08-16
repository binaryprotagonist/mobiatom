<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\Organisationid;
use App\Model\Organisation;
use App\Model\SalesPerson;
use App\Model\CustomerInfo;
use App\Model\Template;
use App\Model\EstimationDetail;
use App\User;

class AssignTemplate extends Model
{
    use SoftDeletes, LogsActivity, Organisationid;

    // protected $table = 'assign_templates';

    protected $fillable = [
        'uuid', 'organisation_id', 'template_id', 'module'
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

	public function template()
    {
        return $this->belongsTo(Template::class,  'template_id', 'id');
    }
}
